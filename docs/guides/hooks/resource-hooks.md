---
title: Resource hooks
---

# Resource hooks

Resource hooks let you inject **relations** and **dashboard widgets** into Filament resources from another plugin. They are **runtime-only** — there is no `ResourceHookListenerInterface`, no discovery, no DB row, no admin toggle.

## Where they fire

A scaffolded resource exposes:

```php
public static function getRelations(): array
{
    return app(\Miran\Mksine\Core\Hooks\ResourceHookManager::class)
        ->applyRelations('post.resource', [
            // …default relation managers…
        ]);
}

public static function getWidgets(): array
{
    return app(\Miran\Mksine\Core\Hooks\ResourceHookManager::class)
        ->applyWidgets('post.resource', [
            // …default widgets…
        ]);
}
```

`ResourceHookManager::applyRelations()` and `applyWidgets()` walk every callback registered for `'post.resource'`, in registration order, feeding each one the **current array** and using its return value if it is an array (anything else is dropped, no exception).

## Adding a relation

```php
use Miran\Mksine\Core\Hooks\Hooks;
use Acme\MyPlugin\Filament\RelationManagers\PostCommentsRelationManager;

public function boot(): void
{
    Hooks::extendResourceRelations('post.resource', function (array $relations) {
        $relations[] = PostCommentsRelationManager::class;

        return $relations;
    });
}
```

The relation manager class itself lives in your plugin and is autoloaded normally.

## Adding a widget

```php
use Miran\Mksine\Core\Hooks\Hooks;
use Acme\MyPlugin\Filament\Widgets\PostStatsWidget;

public function boot(): void
{
    Hooks::extendResourceWidgets('post.resource', function (array $widgets) {
        $widgets[] = PostStatsWidget::class;

        return $widgets;
    });
}
```

## Naming

Resource names follow `'<resource>.resource'`. The convention is **not** enforced anywhere; the name is whatever the resource passes to `applyRelations`/`applyWidgets`. If you publish a resource you want plugins to extend, document the name in your plugin’s README.

## What you don’t get

- No discovery.
- No admin enable/disable.
- No priority surface (callbacks run in registration order).
- No exception isolation. A throwing callback crashes the page.

If you need any of the above, model the behaviour as an **event hook** and have the resource emit an event the listener can act on. Resource hooks are a thin Filament-specific convenience, not a general extension point.

## When _not_ to use them

- For business logic that runs on save / delete — use [event hooks](event-hooks.md), not a relation manager mutation.
- For cross-cutting features (audit log, activity feed) — those should be event-driven, not bolted on as widgets.

## See also

- [Two hook families](overview-two-families.md)
- [Page header hooks](page-header-hooks.md)
- Reference: [`ResourceHookManager`](../../reference/facades-and-managers.md#resourcehookmanager)
