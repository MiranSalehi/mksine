---
title: API stability
description: Public surface of miran/mksine and what semver covers.
order: 5
---

# API stability

This page is the **single source of truth** for which classes, interfaces, commands, and config keys are part of the public API of `miran/mksine`. Everything else is internal and may change in any release.

## Versioning rules

- **Public surface (this page):** semver applies. Backward-incompatible changes go in a major release; additive changes go in a minor release; bugfixes that do not change behavior contracts go in a patch.
- **Anything not listed here:** internal. Changes may land in minor or patch releases without a deprecation cycle. If you depend on internal classes, pin to an exact version.
- **Database schema** of MKSine tables is part of the public surface only when accessed via the documented Eloquent models. Direct schema dependencies (raw migrations renaming columns, etc.) are not covered.

See [Versioning](../meta/versioning.md) for the full release-cadence rules and [Upgrade guide](../meta/upgrade-guide.md) for the running list of breaking changes.

## Public extension points (interfaces)

Implementations of these interfaces are how third-party code extends MKSine.

| Interface | Source | Purpose | Reference |
|-----------|--------|---------|-----------|
| `Miran\Mksine\Core\Plugins\Contracts\PluginInterface` | [PluginInterface.php](../../src/Core/Plugins/Contracts/PluginInterface.php) | A plugin lifecycle and discovery paths | [contracts.md#plugininterface](contracts.md#plugininterface) |
| `Miran\Mksine\Core\Plugins\Contracts\PluginApiInterface` | [PluginApiInterface.php](../../src/Core/Plugins/Contracts/PluginApiInterface.php) | Optional plugin-to-plugin API surface | [contracts.md#pluginapiinterface](contracts.md#pluginapiinterface) |
| `Miran\Mksine\Core\Plugins\Contracts\RegistersFilamentPlugins` | [RegistersFilamentPlugins.php](../../src/Core/Plugins/Contracts/RegistersFilamentPlugins.php) | Add Filament plugins from a CMS plugin | [contracts.md#registersfilamentplugins](contracts.md#registersfilamentplugins) |
| `Miran\Mksine\Core\Hooks\MksineListenerInterface` | [MksineListenerInterface.php](../../src/Core/Hooks/MksineListenerInterface.php) | Event hook listener | [contracts.md#mksinelistenerinterface](contracts.md#mksinelistenerinterface) |
| `Miran\Mksine\Core\Hooks\FormHookListenerInterface` | [FormHookListenerInterface.php](../../src/Core/Hooks/FormHookListenerInterface.php) | Form hook listener (Filament `Schema`) | [contracts.md#formhooklistenerinterface](contracts.md#formhooklistenerinterface) |
| `Miran\Mksine\Core\Hooks\TableHookListenerInterface` | [TableHookListenerInterface.php](../../src/Core/Hooks/TableHookListenerInterface.php) | Table hook listener (Filament `Table`) | [contracts.md#tablehooklistenerinterface](contracts.md#tablehooklistenerinterface) |
| `Miran\Mksine\Core\Hooks\HookAsyncDispatcherInterface` | [HookAsyncDispatcherInterface.php](../../src/Core/Hooks/HookAsyncDispatcherInterface.php) | Pluggable async backend for queued listeners | [contracts.md#hookasyncdispatcherinterface](contracts.md#hookasyncdispatcherinterface) |
| `Miran\Mksine\Core\Events\QueueableHookEventInterface` | [QueueableHookEventInterface.php](../../src/Core/Events/QueueableHookEventInterface.php) | Mark an event as serializable for queue dispatch | [contracts.md#queueablehookeventinterface](contracts.md#queueablehookeventinterface) |
| `Miran\Mksine\Core\Contracts\MksUserInterface` | [MksUserInterface.php](../../src/Core/Contracts/MksUserInterface.php) | Minimal user contract for CMS integrations | [contracts.md#mksuserinterface](contracts.md#mksuserinterface) |
| `Miran\Mksine\Core\PageBuilder\Contracts\BuilderComponentInterface` | [BuilderComponentInterface.php](../../src/Core/PageBuilder/Contracts/BuilderComponentInterface.php) | Page builder block contract | [contracts.md#buildercomponentinterface](contracts.md#buildercomponentinterface) |
| `Miran\Mksine\Contracts\MenuItemSourceInterface` | [MenuItemSourceInterface.php](../../src/Contracts/MenuItemSourceInterface.php) | Custom menu item source | [contracts.md#menuitemsourceinterface](contracts.md#menuitemsourceinterface) |
| `Miran\Mksine\Contracts\MenuItemSourcePaginatedInterface` | [MenuItemSourcePaginatedInterface.php](../../src/Contracts/MenuItemSourcePaginatedInterface.php) | Pagination + search extension for large sources | [contracts.md#menuitemsourcepaginatedinterface](contracts.md#menuitemsourcepaginatedinterface) |

## Public abstract base classes

| Class | Source | Purpose |
|-------|--------|---------|
| `Miran\Mksine\Core\PageBuilder\BaseBuilderComponent` | [BaseBuilderComponent.php](../../src/Core/PageBuilder/BaseBuilderComponent.php) | Default implementation of `BuilderComponentInterface` |

## Public facades and managers

| Public binding | Resolves to | Use for |
|----------------|-------------|---------|
| `Miran\Mksine\Facades\Mksine` | `Miran\Mksine\Mksine` | Package metadata and feature toggles |
| `Miran\Mksine\Core\Hooks\Hooks` (static helper, not a `Facade`) | itself | Imperative hook registration and runtime extension |
| `Miran\Mksine\Core\Hooks\HookManager` | container singleton | Event dispatch and listener registry |
| `Miran\Mksine\Core\Hooks\FormHookManager` | container singleton | Filament form hook dispatch |
| `Miran\Mksine\Core\Hooks\TableHookManager` | container singleton | Filament table hook dispatch |
| `Miran\Mksine\Core\Hooks\ResourceHookManager` | container singleton | Relations and widgets per Filament resource |
| `Miran\Mksine\Core\Hooks\PageHookManager` | container singleton | Page header actions |
| `Miran\Mksine\Core\Hooks\MenuLocationManager` | container singleton | Register named menu locations |
| `Miran\Mksine\Core\Hooks\MenuItemSourceManager` | container singleton | Register `MenuItemSourceInterface` implementations |
| `Miran\Mksine\Core\Hooks\SettingsTabManager` | container singleton | Add tabs on the Settings page |
| `Miran\Mksine\Core\PageBuilder\ComponentRegistry` | container singleton | Register page builder block types |
| `Miran\Mksine\Core\Theme\ThemeManager` | container singleton | Theme discovery, activation, view namespacing, asset publish |

Full method tables: see [facades-and-managers.md](facades-and-managers.md).

## Public Artisan commands

The signatures are stable across minor releases. Adding new options is non-breaking; removing or renaming options is breaking.

| Family | Commands | Reference |
|--------|----------|-----------|
| Plugins | `mks-plugin:make`, `mks-plugin:make-resource`, `mks-plugin:make-page`, `mks-plugin:make-widget`, `mks-plugin:make-model`, `mks-plugin:discover`, `mks-plugin:list`, `mks-plugin:install`, `mks-plugin:activate`, `mks-plugin:deactivate`, `mks-plugin:uninstall`, `mks-plugin:migrate`, `mks-plugin:publish`, `mks-plugin:publish-lang` | [commands.md#plugins](commands.md#plugins) |
| Themes | `mks:make-theme`, `mks:theme-publish`, `mks:theme-publish-lang` | [commands.md#themes](commands.md#themes) |
| Hooks | `mks:discover` | [commands.md#hooks](commands.md#hooks) |
| Release | `mks:release-archive` | [commands.md#release](commands.md#release) |
| Package install / info | `mksine:install`, `mksine:info`, `mksine:artisan`, `mksine:fresh-super-admin` | [commands.md#package](commands.md#package) |

## Public configuration keys

Every key in [`config/mksine.php`](../../config/mksine.php) is public; defaults may change in minor releases when documented in the [Upgrade guide](../meta/upgrade-guide.md). Full table: [configuration.md](configuration.md).

## Explicitly **not** public

These are internal even though they live in `src/`:

- `Miran\Mksine\Core\Plugins\PluginManager` — orchestration; call sites and methods may shift between minor versions. Use `mks-plugin:*` commands or the `Mksine` facade instead.
- `Miran\Mksine\Core\Plugins\PluginDiscovery`, `PluginLifecycle`, `PluginRegistry`, `PluginManifest` — discovery internals.
- `Miran\Mksine\Core\Plugins\Publishing\PluginVendorPublishRunner` — used by per-plugin publish-vendor commands; helper API but no semver guarantee yet (will be promoted once stable, see [Upgrade guide](../meta/upgrade-guide.md)).
- `Miran\Mksine\Core\Hooks\HookDispatcher`, `HookCacheStore`, all listener discovery internals.
- Everything under `Miran\Mksine\Filament\` (resources, pages, livewire components). Customize via hooks, not by extending these classes.
- Everything under `Miran\Mksine\Models\` is **public** when used as Eloquent models from your code, but their **migrations and column shapes** are internal — use Eloquent attributes/relations rather than raw queries against MKSine tables.

## Deprecation policy

Items removed from the public surface go through one of two paths:

1. **Deprecated, then removed:** marked deprecated in a minor release with a noisy log warning when invoked, removed no earlier than the next major.
2. **Renamed:** old name kept as a deprecated alias for at least one minor release.

Deprecations are listed in `CHANGELOG.md` and the [Upgrade guide](../meta/upgrade-guide.md) for the release that introduces them.
