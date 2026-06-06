---
title: Facades and managers
description: Public facades, the Hooks helper, and every manager singleton MKSine exposes.
order: 3
---

# Facades and managers

These are the runtime entry points consumed by application and plugin code. Every class on this page is part of the [public surface](stability.md).

## `Mksine` facade

| Item | Source |
|------|--------|
| Facade | [`Miran\Mksine\Facades\Mksine`](../../src/Facades/Mksine.php) |
| Underlying class | [`Miran\Mksine\Mksine`](../../src/Mksine.php) |

Tiny package-metadata helper. Resolves `Miran\Mksine\Mksine` from the container.

```php
namespace Miran\Mksine;

class Mksine
{
    public function version(): string;                               // mksine.version
    public function isFeatureEnabled(string $feature): bool;         // mksine.features.{feature}
    public function config(?string $key = null, mixed $default = null): mixed; // mksine.{key}
}
```

Common usage:

```php
use Miran\Mksine\Facades\Mksine;

Mksine::version();                          // '1.0.0'
Mksine::isFeatureEnabled('page_builder');   // bool
Mksine::config('plugins_path');             // 'plugins' (or your override)
```

## `Hooks` static helper

`Miran\Mksine\Core\Hooks\Hooks` — [source](../../src/Core/Hooks/Hooks.php).

A thin static façade over the hook managers. Not a Laravel `Facade` — it is a class with public static methods that resolve the right manager from the container.

| Static method | Purpose |
|---------------|---------|
| `Hooks::manager(): HookManager` | Resolve the event manager (singleton). |
| `Hooks::formManager(): FormHookManager` | Resolve the form manager. |
| `Hooks::tableManager(): TableHookManager` | Resolve the table manager. |
| `Hooks::resourceManager(): ResourceHookManager` | Resolve the resource manager. |
| `Hooks::pageManager(): PageHookManager` | Resolve the page manager. |
| `Hooks::register(string $eventName, string $listenerClass, int $priority = 0): void` | Register an event listener at runtime. |
| `Hooks::extendForm(string $formName, callable $callback): void` | Register a callable form extender. |
| `Hooks::extendTable(string $tableName, callable $callback): void` | Register a callable table extender. |
| `Hooks::extendTableColumns(string $tableName, callable $callback): void` | Modify columns (callable receives the `Table`). |
| `Hooks::extendTableActions(string $tableName, callable $callback): void` | Modify record actions. |
| `Hooks::extendTableBulkActions(string $tableName, callable $callback): void` | Modify bulk actions. |
| `Hooks::extendTableFilters(string $tableName, callable $callback): void` | Modify filters. |
| `Hooks::extendResourceRelations(string $resourceName, callable $callback): void` | Add or modify resource relations. |
| `Hooks::extendResourceWidgets(string $resourceName, callable $callback): void` | Add or modify resource widgets. |
| `Hooks::extendPageHeaderActions(string $pageName, callable $callback): void` | Add header actions to a page. |
| `Hooks::enableListener(string $listenerClass): void` | **Deprecated.** State is in `mks_hooks`. |
| `Hooks::disableListener(string $listenerClass): void` | **Deprecated.** State is in `mks_hooks`. |
| `Hooks::setPriority(string $listenerClass, int $priority): void` | **Deprecated.** State is in `mks_hooks`. |

Use `Hooks::` for **runtime** registration (in plugin `boot()` or service providers). For **discoverable, persistent** registration use the listener interfaces and `mks:discover`. See [Two families overview](../guides/hooks/overview-two-families.md).

## Managers

All managers are container singletons. Resolve via `app(ManagerClass::class)` or via `Hooks::*Manager()` where applicable.

### `HookManager`

[`Miran\Mksine\Core\Hooks\HookManager`](../../src/Core/Hooks/HookManager.php).

Coordinator for the event hook system. Final + immutable execution lifecycle (see the class header docblock).

```php
public function register(string $eventName, string $listenerClass, int $priority = 0): void;
public function dispatch(\Miran\Mksine\Core\Events\MksineEvent $event): \Miran\Mksine\Core\Hooks\EventResult;
public function isListenerEnabled(string $listenerClass): bool;
public function getRegisteredListeners(): array;
public function clearCache(): void;

// Deprecated (no-ops, log warnings in debug mode):
public function enableListener(string $listenerClass): void;
public function disableListener(string $listenerClass): void;
public function setPriority(string $listenerClass, int $priority): void;
```

Notes:

- `dispatch()` follows the canonical 8-phase lifecycle defined in [`HookLifecycle`](../../src/Core/Hooks/HookLifecycle.php). The `HookDispatcher` and `HookRegistry` classes are `final` and may not be replaced.
- System listeners (`is_system = true` in `mks_hooks`) execute even if marked `is_enabled = false`.
- `clearCache()` clears in-memory listener instances and state; useful in tests.

### `FormHookManager`

[`Miran\Mksine\Core\Hooks\FormHookManager`](../../src/Core/Hooks/FormHookManager.php).

```php
public function extend(string $formName, callable $callback): void;
public function apply(string $formName, \Filament\Schemas\Schema $schema): \Filament\Schemas\Schema;
public function clear(string $formName): void;
```

Callbacks receive the original `Schema` and **must return** a `Schema`. Errors thrown inside a callback are caught and logged so other listeners still run (see source for the `try/catch`).

### `TableHookManager`

[`Miran\Mksine\Core\Hooks\TableHookManager`](../../src/Core/Hooks/TableHookManager.php).

```php
public function extend(string $tableName, callable $callback): void;
public function extendColumns(string $tableName, callable $callback): void;
public function extendActions(string $tableName, callable $callback): void;
public function extendBulkActions(string $tableName, callable $callback): void;
public function extendFilters(string $tableName, callable $callback): void;
public function apply(string $tableName, \Filament\Tables\Table $table): \Filament\Tables\Table;
public function clear(string $tableName): void;
```

Order of application inside `apply()`:

1. `extend()` callbacks (most flexible).
2. `extendColumns()`.
3. `extendActions()`.
4. `extendBulkActions()`.
5. `extendFilters()`.

Each callback receives the `Table` instance and must return a `Table`.

### `ResourceHookManager`

[`Miran\Mksine\Core\Hooks\ResourceHookManager`](../../src/Core/Hooks/ResourceHookManager.php).

```php
public function extendRelations(string $resourceName, callable $callback): void;
public function extendWidgets(string $resourceName, callable $callback): void;
public function applyRelations(string $resourceName, array $relations): array;
public function applyWidgets(string $resourceName, array $widgets): array;
public function clear(string $resourceName): void;
```

Resource keys follow the `{name}.resource` convention (for example `post.resource`). Callbacks receive an array and must return an array.

### `PageHookManager`

[`Miran\Mksine\Core\Hooks\PageHookManager`](../../src/Core/Hooks/PageHookManager.php).

```php
public function extendHeaderActions(string $pageName, callable $callback): void;
public function applyHeaderActions(string $pageName, array $actions): array;
public function clear(string $pageName): void;
```

Page identifiers follow the `{name}.{view}` convention (`post.list`, `post.edit`).

### `MenuLocationManager`

[`Miran\Mksine\Core\Hooks\MenuLocationManager`](../../src/Core/Hooks/MenuLocationManager.php).

```php
public function registerLocation(string $key, string $label): void;
public function registerLocations(array $locations): void;
public function getLocations(): array;
public function getLocationLabel(string $key): ?string;
public function syncToDatabase(): void;
```

Locations registered programmatically must be persisted with `syncToDatabase()` so the Filament UI sees them. Sync is non-destructive: it never deletes locations missing from code (existing menu assignments stay intact).

### `MenuItemSourceManager`

[`Miran\Mksine\Core\Hooks\MenuItemSourceManager`](../../src/Core/Hooks/MenuItemSourceManager.php).

```php
public function register(string $key, \Miran\Mksine\Contracts\MenuItemSourceInterface $source): void;
public function getSources(): array;
public function getSource(string $key): ?\Miran\Mksine\Contracts\MenuItemSourceInterface;
public function hasSource(string $key): bool;
public function unregister(string $key): void;
public function getSourceKeys(): array;
```

Implementations must satisfy [`MenuItemSourceInterface`](contracts.md#menuitemsourceinterface). Large lists should additionally implement [`MenuItemSourcePaginatedInterface`](contracts.md#menuitemsourcepaginatedinterface).

### `SettingsTabManager`

[`Miran\Mksine\Core\Hooks\SettingsTabManager`](../../src/Core/Hooks/SettingsTabManager.php).

```php
public function registerTab(
    string $id,
    string|\Closure $label,
    array|callable $schema,
    int $sortOrder = 0
): void;

public function getTabs(): array; // array<\Filament\Schemas\Components\Tabs\Tab>
public function clear(): void;
```

- Use a `Closure` for `$label` when the label needs translation via `__()`; the closure runs at render time.
- `$schema` may be an array of Filament components or a callable returning that array (also evaluated at render time).
- Field values persist via the `Setting` model the same way as core tabs.

### `ComponentRegistry`

[`Miran\Mksine\Core\PageBuilder\ComponentRegistry`](../../src/Core/PageBuilder/ComponentRegistry.php).

```php
public function register(string $componentClass): self;
public function registerMany(array $componentClasses): self;
public function get(string $type): ?string;
public function has(string $type): bool;
public function all(): array;
public function toArray(): array;
public function getByCategory(): \Illuminate\Support\Collection;
public static function getCategoryMeta(): array;
public function getSchema(string $type): array;
public function createInstance(string $type, ?string $id = null): ?array;
public function validateComponent(string $type, array $data): array;
public function resolveRenderView(string $type): string;
```

- `$componentClass` must implement [`BuilderComponentInterface`](contracts.md#buildercomponentinterface); the registry throws `InvalidArgumentException` otherwise.
- `resolveRenderView()` returns the Blade view used to render a block. For classes extending `BaseBuilderComponent`, the registry uses `getRenderView()`. For other classes it falls back to `mksine::page-builder.render.{type}`.
- `getCategoryMeta()` exposes the icons/order used by the picker for the built-in categories (`content`, `media`, `layout`, `interactive`, `sections`).

### `ThemeManager`

[`Miran\Mksine\Core\Theme\ThemeManager`](../../src/Core/Theme/ThemeManager.php).

```php
public function discover(bool $fresh = false): \Illuminate\Support\Collection;
public function get(string $identifier): ?\Miran\Mksine\Core\Theme\ThemeData;
public function getActive(): ?\Miran\Mksine\Core\Theme\ThemeData;
public function getDefault(): ?\Miran\Mksine\Core\Theme\ThemeData;
public function activate(string $identifier): bool;

public function getViewNamespace(): string;
public function view(string $view): string;
public function layout(): string;
public function asset(string $path): string;

public function publishAssets(string $identifier): bool;
public function publishThemeTranslations(string $identifier): bool;
public function getThemeTranslationsPath(\Miran\Mksine\Core\Theme\ThemeData $theme): ?string;

public function clearCache(): void;
public function getScreenshotUrl(\Miran\Mksine\Core\Theme\ThemeData $theme): ?string;

public function getCustomStorageDir(): string;
public function getCustomStoragePath(string $identifier, string $type): string;       // 'css'|'js'
public function hasCustomAsset(string $identifier, string $type): bool;
public function getCustomContent(string $identifier, string $type): string;
public function putCustomContent(string $identifier, string $type, string $content): bool;

public function getExtraAssetsStoragePath(string $identifier): string;
public function getExtraAssets(string $identifier): array;                            // ['css' => [...], 'js' => [...]]
public function setExtraAssets(string $identifier, array $css, array $js): bool;

public function registerProjectThemeViews(): void;
public function getPackageThemesPath(): string;
public function getProjectThemesPath(): string;
```

Notes:

- `discover()` caches under key `mksine.themes.discovered` (TTL 3600s). Pass `true` to bust the cache after on-disk changes.
- `getViewNamespace()` returns `mksine::themes.{id}` for package themes and `theme::{id}` for project themes (registered via `registerProjectThemeViews()`).
- `asset()` returns the public URL appropriate for the theme’s source: project themes resolve under `themes/{id}/`, package themes under `vendor/mksine/themes/{id}/`.
- The custom-asset and extra-asset helpers persist admin-edited overrides under `storage/app/theme-custom/`. They survive deploys only if you back up the storage path.

See [Theme guides](../guides/themes/creating-a-theme.md).

### `StoreGeoSettings` and `GeoResolver`

[`StoreGeoSettings`](../../src/Services/Geo/StoreGeoSettings.php) (singleton) reads geo preferences from `mks_setting()` (`geo_enabled_countries`, `geo_default_country`, `geo_address_levels` with legacy `ecom_*` fallback).

[`GeoResolver`](../../src/Services/Geo/GeoResolver.php) (container-resolved) queries enabled countries/states/cities and formats display names. Use `GeoResolver::make()` in application code.

See [Global geo system](../guides/geo/overview.md).

## See also

- [Contracts](contracts.md) — interfaces these managers consume.
- [Configuration](configuration.md) — config keys consumed by the managers.
- [Hook overview](../guides/hooks/overview-two-families.md) — how the runtime helpers and the discovery family fit together.
