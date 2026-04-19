---
title: Filament pages and widgets from plugins
description: Generators, namespaces, and the hook surface for standalone Filament pages and dashboard widgets.
order: 13
---

# Filament pages and widgets from plugins

Plugins ship Filament pages from `src/Filament/Pages/` and widgets from `src/Filament/Widgets/`. Like resources (see [Filament resources](filament-resources.md)), these are auto-registered when the plugin is active and `namespace()` returns a value.

## Standalone pages

### Generate

```bash
php artisan mks-plugin:make-page my-plugin Dashboard
```

Source: [`PluginMakePageCommand`](../../../src/Console/Commands/PluginMakePageCommand.php). Creates:

- `src/Filament/Pages/Dashboard.php`
- `resources/views/filament/pages/dashboard.blade.php`

The page extends `Filament\Pages\Page`. Default route slug is the kebab-case of the class name; override via `protected static ?string $slug = '...'` if you need a custom path.

### Wire it up

Standalone pages must:

- Set `protected static string $view` to the published view name (the generator does this).
- Be inside `{namespace}\Filament\Pages` for auto-discovery.
- Be authorised — by default Filament protects pages with `viewAny` permission on the page class. Generate a Shield permission or override `static::canAccess(): bool` accordingly.

For navigation, use the standard Filament `$navigationIcon`, `$navigationLabel`, `$navigationGroup`, `$navigationSort`.

### Header action hooks

If you want **other plugins** (or your app code) to extend the page header, call the manager in `getHeaderActions()`:

```php
use Miran\Mksine\Core\Hooks\Hooks;

protected function getHeaderActions(): array
{
    return Hooks::pageManager()->applyHeaderActions(
        'Dashboard.page',
        [
            // your default actions
        ]
    );
}
```

Convention for the key: `{PageClassBasename}.page`. Use whatever you want — just publish it in your plugin docs so consumers know which key to register against. See [Page header actions](../hooks/page-header-actions.md).

## Widgets

### Generate

```bash
# Basic widget with a Blade view
php artisan mks-plugin:make-widget my-plugin LatestOrders

# Chart widget (line chart with sample data)
php artisan mks-plugin:make-widget my-plugin SalesChart --chart

# Stats overview widget (3 sample stats)
php artisan mks-plugin:make-widget my-plugin SalesStats --stats
```

Source: [`PluginMakeWidgetCommand`](../../../src/Console/Commands/PluginMakeWidgetCommand.php). Outputs depend on the flag:

| Flag | Base class | Extra files |
|------|-----------|-------------|
| _(none)_ | `Filament\Widgets\Widget` | `resources/views/filament/widgets/{kebab-name}.blade.php` |
| `--chart` | `Filament\Widgets\ChartWidget` | none — extends only |
| `--stats` | `Filament\Widgets\StatsOverviewWidget` | none |

`--chart` and `--stats` are mutually exclusive. If you pass both, `--chart` wins (verified in code).

### Where widgets show up

Filament does not register plugin widgets to a page automatically. You have two patterns:

**Pattern A — Resource widgets.** Use [resource hooks](../hooks/resource-hooks.md) to inject your widget into another resource’s list/edit page:

```php
Hooks::extendResourceWidgets('Item.resource', function (array $widgets): array {
    $widgets[] = \MyPlugin\Filament\Widgets\LatestOrders::class;
    return $widgets;
});
```

**Pattern B — Dashboard widgets.** Register on the panel directly. The cleanest place is your plugin’s `boot()` if it implements [`RegistersFilamentPlugins`](../../reference/contracts.md#registersfilamentplugins) (see [Registering Filament plugins](filament-plugins.md)). Otherwise, ask the host application to register your widget on its panel — plugins should not modify another plugin’s panel.

### Per-widget authorisation

Filament respects `static::canView(): bool`. Override it for widgets that should appear only for certain roles:

```php
public static function canView(): bool
{
    return auth()->user()?->can('view-sales-stats') ?? false;
}
```

Generated widgets do **not** include this guard. Add it before going to production.

## View namespaces

The widget generator builds the view name as `{Str::kebab(class_basename($namespace))}::filament.widgets.{view-name}`. For `MyPlugin\Filament\Widgets\LatestOrders`, that is `my-plugin::filament.widgets.latest-orders`.

For this to resolve, MKSine must register your plugin as a view namespace, which it does automatically when `viewsPath()` returns a directory. If your views live elsewhere, register them in `boot()`:

```php
public function boot(): void
{
    view()->addNamespace('my-plugin', __DIR__ . '/../resources/views');
}
```

## Pitfalls

- **Forgetting `static::$view`** on a basic widget yields a `View [filament-widgets::widget] not found`-style error.
- **Using polling on chart widgets** without rate-limiting the data source — each panel session triggers `getData()` periodically. Cache the result.
- **Widget that mutates database in `getStats()`** — these run on every render. Read-only queries, please.
- **Page class not in `Filament\Pages` namespace** but registered manually in panel provider: nothing wrong with it, but you lose plugin auto-discovery. Either follow the convention or register everywhere it’s needed.

## See also

- [Filament resources](filament-resources.md) — companion guide for resource scaffolding.
- [Page header actions](../hooks/page-header-actions.md) — extending header actions from other plugins.
- [Resource hooks](../hooks/resource-hooks.md) — placing your widget on another resource.
