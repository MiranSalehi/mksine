---
title: Contracts
description: Every public interface in miran/mksine, with full method signatures.
order: 2
---

# Contracts

Every interface listed here is part of the [public surface](stability.md). Implementations of these interfaces are how third-party code extends MKSine. Internal helper interfaces are intentionally excluded.

Group the contracts into five families:

1. [Plugin contracts](#plugin-contracts) — lifecycle, plugin-to-plugin API, Filament panel registration.
2. [Hook contracts](#hook-contracts) — event/form/table listener interfaces and async dispatch.
3. [Event contracts](#event-contracts) — queueable event payloads.
4. [Page builder contracts](#page-builder-contracts) — block contract.
5. [Menu contracts](#menu-contracts) — item sources for the menu builder.
6. [User contract](#user-contract) — minimal user shape consumed by CMS code.

Each section gives the FQCN, the source path, the full PHP signature copied from the package, and an intent paragraph.

## Plugin contracts

### `PluginInterface`

`Miran\Mksine\Core\Plugins\Contracts\PluginInterface` — [source](../../src/Core/Plugins/Contracts/PluginInterface.php).

Implemented by every plugin’s `plugin_class`. Defines the lifecycle hooks called by `PluginManager`, plus discovery getters that expose the plugin’s on-disk paths (Filament resources, views, routes, translations, …).

```php
interface PluginInterface
{
    public function id(): string;

    public function install(): void;
    public function activate(): void;
    public function deactivate(): void;
    public function uninstall(bool $deleteData = false): void;
    public function boot(): void;

    public function migrationsPath(): ?string;
    public function configPath(): ?string;
    public function viewsPath(): ?string;
    public function webRoutesPath(): ?string;
    public function apiRoutesPath(): ?string;
    public function translationsPath(): ?string;

    public function filamentResourcesPath(): ?string;
    public function filamentPagesPath(): ?string;
    public function filamentWidgetsPath(): ?string;

    public function namespace(): ?string;
}
```

Method notes:

- `id()` must equal the `id` value in `plugin.php`. The folder name on disk is the discovery anchor; mismatch breaks resolution.
- `install`, `activate`, `deactivate`, `uninstall` run from the `mks-plugin:*` lifecycle commands. They run inside the standard Laravel container, so dependency injection through `app()` is fine. Errors here surface in `mks_plugins.boot_error` (for `boot()`) and as Artisan failures (for the rest).
- `boot()` runs **on every request** when the plugin is active. Keep it cheap; do not migrate, seed, or hit external services here. The boot guard (see [Configuration → `plugins.boot_guard_ttl`](configuration.md#plugins)) deactivates plugins whose `boot()` flag stays "still booting" longer than the TTL.
- `uninstall(bool $deleteData = false)` must respect the flag. With `false`, drop registrations only; with `true`, also drop tables and files. The `mks-plugin:uninstall {plugin} --delete-data` flag forwards this argument.
- The `…Path()` getters return absolute filesystem paths. Returning `null` tells `PluginManager` "this plugin does not ship that artifact".
- `namespace()` controls Filament autoloading for the `filament*Path()` directories; it must match the namespace in your scaffolded classes.

See [Plugin lifecycle](../guides/plugins/lifecycle.md) for state transitions and [Plugin golden path](../guides/plugins/golden-path.md) for a complete implementation.

### `PluginApiInterface`

`Miran\Mksine\Core\Plugins\Contracts\PluginApiInterface` — [source](../../src/Core/Plugins/Contracts/PluginApiInterface.php).

Optional. Implement when your plugin exposes a public API for **other plugins** to consume.

```php
interface PluginApiInterface
{
    public static function getFacadeClass(): ?string;
    public static function getContainerBinding(): string;
}
```

- `getFacadeClass()` is the FQCN of a Laravel facade you ship with your plugin. Return `null` to expose only a container binding.
- `getContainerBinding()` is the abstract name registered in the container; consumers resolve it via `app($yourPlugin::getContainerBinding())`.

See [Plugin-to-plugin API](../guides/plugins/plugin-api.md).

### `RegistersFilamentPlugins`

`Miran\Mksine\Core\Plugins\Contracts\RegistersFilamentPlugins` — [source](../../src/Core/Plugins/Contracts/RegistersFilamentPlugins.php).

Optional. Implement when your CMS plugin needs to register one or more **Filament plugins** on the panel without touching the host app’s panel provider.

```php
interface RegistersFilamentPlugins
{
    /**
     * @return array<int, \Filament\Contracts\Plugin>
     */
    public function filamentPlugins(\Filament\Panel $panel): array;
}
```

- The `Panel` parameter is the panel currently being booted; use it for conditional registration (`$panel->getId() === 'admin'`).
- Return an empty array if the plugin should not contribute to this panel.

See [Registering Filament plugins](../guides/plugins/filament-plugins.md).

## Hook contracts

### `MksineListenerInterface`

`Miran\Mksine\Core\Hooks\MksineListenerInterface` — [source](../../src/Core/Hooks/MksineListenerInterface.php).

Event listener for the discovery family. Discovered by `mks:discover` from any path under `Core/Listeners` or `mksine.hooks.discovery_paths`.

```php
interface MksineListenerInterface
{
    public function handle(\Miran\Mksine\Core\Events\MksineEvent $event): void;
    public function shouldHandle(\Miran\Mksine\Core\Events\MksineEvent $event): bool;
    public function shouldQueue(): bool;
    public function priority(): int;
}
```

- `handle()` runs whenever `HookManager::dispatch($event)` fires the matching event name and `shouldHandle()` returns `true`.
- `shouldHandle()` short-circuits this listener without consuming a slot in priority ordering.
- `shouldQueue()` opts the listener into asynchronous dispatch via `HookAsyncDispatcherInterface` **only when the event implements `QueueableHookEventInterface` and async is enabled** (`mksine.hooks.queue.enabled = true` and `MksineEvent::isAsyncAllowed() === true`).
- `priority()` is the integer ordering: lower runs first. The DB row in `mks_hooks` may override this when a row is present.

See [Event hooks](../guides/hooks/event-hooks.md), [Async and queues](../guides/hooks/async-and-queues.md).

### `FormHookListenerInterface`

`Miran\Mksine\Core\Hooks\FormHookListenerInterface` — [source](../../src/Core/Hooks/FormHookListenerInterface.php).

Filament form extension. Discovered by `mks:discover`.

```php
interface FormHookListenerInterface
{
    public static function getFormName(): string;
    public static function getPriority(): int;
    public static function extend(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema;
}
```

- `getFormName()` returns the form key MKSine resources use, e.g. `post.form`, `user.form`. Misnaming is the most common cause of "hook not applied".
- `getPriority()` is static; returning `0` keeps default ordering.
- `extend()` receives the original `Schema` and **must return** a `Schema` (the same one or a new one). Returning `void` is silently ignored by `FormHookManager` (see [its source](../../src/Core/Hooks/FormHookManager.php)).

See [Form hooks](../guides/hooks/form-hooks.md).

### `FormSlotHookListenerInterface`

`Miran\Mksine\Core\Hooks\FormSlotHookListenerInterface` — [source](../../src/Core/Hooks/FormSlotHookListenerInterface.php).

Named form slot extension (`before` / `after` / `replace`). Discovered by `mks:discover` as `hook_type = form_slot`.

```php
interface FormSlotHookListenerInterface
{
    public static function getFormName(): string;
    public static function getPosition(): string; // before|after|replace
    public static function getAnchor(): string;
    public static function getPriority(): int;
    public static function handle(\Filament\Schemas\Components\Component $original): \Filament\Schemas\Components\Component|array|null;
}
```

- `hook_name` in `mks_hooks` is `"{formName}.{position}.{anchor}"`.
- For `replace`, return `null` or `[]` to hide the original component. Last successful replace wins.
- Prefer runtime `Hooks::beforeFormComponent()` / `afterFormComponent()` / `replaceFormComponent()` for closures.

See [Named slot hooks](../guides/hooks/form-hooks.md#named-slot-hooks).

### `TableHookListenerInterface`

`Miran\Mksine\Core\Hooks\TableHookListenerInterface` — [source](../../src/Core/Hooks/TableHookListenerInterface.php).

Filament table extension. Discovered by `mks:discover`.

```php
interface TableHookListenerInterface
{
    public static function getTableName(): string;
    public static function getPriority(): int;
    public static function extend(\Filament\Tables\Table $table): \Filament\Tables\Table;
}
```

Same shape as the form listener but for tables (e.g. `post.table`). See [Table hooks](../guides/hooks/table-hooks.md).

### `HookAsyncDispatcherInterface`

`Miran\Mksine\Core\Hooks\HookAsyncDispatcherInterface` — [source](../../src/Core/Hooks/HookAsyncDispatcherInterface.php).

Pluggable backend for asynchronous listener dispatch. The default binding is `Miran\Mksine\Core\Hooks\LaravelHookAsyncDispatcher`, registered when `mksine.hooks.queue.enabled = true`.

```php
interface HookAsyncDispatcherInterface
{
    /**
     * @param  array{v: int, data: array<string, mixed>, context?: array<string, mixed>}  $payload
     */
    public function dispatchAsync(string $listenerClass, string $eventClass, array $payload): void;
}
```

- The payload comes from `QueueableHookEventInterface::toQueuePayload()` and **must** contain an integer `v` key. `HookManager` enforces that.
- Bind your own implementation in a service provider when you need a non-Laravel queue backend.

See [Async and queues](../guides/hooks/async-and-queues.md).

## Event contracts

### `QueueableHookEventInterface`

`Miran\Mksine\Core\Events\QueueableHookEventInterface` — [source](../../src/Core/Events/QueueableHookEventInterface.php).

Mark an event as serializable so async listeners can reconstruct it inside a worker.

```php
interface QueueableHookEventInterface
{
    /**
     * @return array{v: int, data: array<string, mixed>, context?: array<string, mixed>}
     */
    public function toQueuePayload(): array;

    /**
     * @param  array{v: int, data: array<string, mixed>, context?: array<string, mixed>}  $payload
     */
    public static function fromQueuePayload(array $payload): static;
}
```

- Payload **must** be primitives + identifiers; Eloquent models are forbidden because they cannot reliably serialize across deploys. Re-fetch models inside the listener.
- The integer `v` key is the **payload schema version**. Bump it whenever you change the shape; handle older `v` values in `fromQueuePayload()` to avoid losing in-flight jobs during a deploy.
- If an event returns `allowAsync() === true` but does not implement this interface, `HookManager` throws a `LogicException` at dispatch time (see [`HookManager::dispatchPendingAsyncListeners`](../../src/Core/Hooks/HookManager.php)).

## Page builder contracts

### `BuilderComponentInterface`

`Miran\Mksine\Core\PageBuilder\Contracts\BuilderComponentInterface` — [source](../../src/Core/PageBuilder/Contracts/BuilderComponentInterface.php).

Implemented by every page builder block. Most blocks should extend [`BaseBuilderComponent`](../../src/Core/PageBuilder/BaseBuilderComponent.php) instead of writing the interface from scratch.

```php
interface BuilderComponentInterface
{
    public static function getType(): string;
    public static function getName(): string;
    public static function getIcon(): string;
    public static function getCategory(): string;
    public static function getDescription(): string;

    public static function getSchema(): array;
    public static function getDefaultData(): array;

    public static function supportsChildren(): bool;
    public static function getMaxChildren(): ?int;

    public static function validate(array $data): array;
}
```

Method notes:

- `getType()` is the registry key; must be unique across all registered components. Stable values like `heading`, `image`, `columns`.
- `getCategory()` should be one of the constants on `BaseBuilderComponent` (`CATEGORY_CONTENT`, `CATEGORY_MEDIA`, `CATEGORY_LAYOUT`, `CATEGORY_INTERACTIVE`, `CATEGORY_SECTIONS`); custom categories work but lose the standard icon/order metadata in the picker.
- `getSchema()` returns an array of Filament form components for the editor.
- `supportsChildren()` plus `getMaxChildren()` control nesting; `getMaxChildren()` returning `null` means unlimited.
- `validate()` receives the raw editor data and returns a normalized version (or throws if invalid). Do not rely on this for security boundaries — sanitize on render too.

See [Creating a block](../guides/page-builder/creating-a-block.md).

## Menu contracts

### `MenuItemSourceInterface`

`Miran\Mksine\Contracts\MenuItemSourceInterface` — [source](../../src/Contracts/MenuItemSourceInterface.php).

Add an item source (categories, products, custom posts, …) to the Menu Builder UI.

```php
interface MenuItemSourceInterface
{
    public function getKey(): string;
    public function getLabel(): string;
    public function getIcon(): string;

    /**
     * @return array<int, array{id: int|string, label: string, url: string}>
     */
    public function getItems(): array;

    /**
     * @return array{type: string, label: string, url: string|null, reference_id: int|null}
     */
    public function toMenuItem(mixed $item): array;

    /**
     * @return array<int, mixed>|null Filament form components, or null for the default checkbox list
     */
    public function getFormSchema(): ?array;

    public function supportsMultipleSelection(): bool;
}
```

- Sources with thousands of items should also implement [`MenuItemSourcePaginatedInterface`](#menuitemsourcepaginatedinterface) to avoid loading the entire list every UI open.
- `toMenuItem()` is called when the user adds an item; the returned array becomes a row in the menu items table.

See [Custom item sources](../guides/menus/custom-sources.md).

### `MenuItemSourcePaginatedInterface`

`Miran\Mksine\Contracts\MenuItemSourcePaginatedInterface` — [source](../../src/Contracts/MenuItemSourcePaginatedInterface.php).

Optional, extends `MenuItemSourceInterface`.

```php
interface MenuItemSourcePaginatedInterface extends MenuItemSourceInterface
{
    /**
     * @return array{
     *   items: array<int, array{id: int, label: string, url: string, parent_id?: int|null}>,
     *   total: int
     * }
     */
    public function getItemsPaginated(string $search, int $page, int $perPage): array;

    /**
     * @param  array<int>  $ids
     * @return array<int, array{id: int, label: string, url: string}>
     */
    public function getItemsByIds(array $ids): array;
}
```

When implemented, the Menu Builder uses these methods instead of `getItems()`/in-memory filtering. Items may include `parent_id` to display a hierarchical picker.

## User contract

### `MksUserInterface`

`Miran\Mksine\Core\Contracts\MksUserInterface` — [source](../../src/Core/Contracts/MksUserInterface.php).

Minimal shape MKSine code expects from any user object passed in — independent of the host’s `auth.providers.users.model`.

```php
interface MksUserInterface
{
    public function getId(): int|string;
    public function getName(): string;
    public function getEmail(): string;
}
```

Most host applications already satisfy this with their `App\Models\User`. If you replace the user class via [User subclass](../guides/auth/user-subclass.md), keep the contract intact.

## See also

- [API stability](stability.md) — what is public and what is not.
- [Facades and managers](facades-and-managers.md) — runtime entry points that consume these contracts.
- [Configuration](configuration.md) — every config key referenced above.
