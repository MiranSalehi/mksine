---
title: Plugins
description: What a MKSine plugin is and how the system thinks about it.
order: 1
---

# Plugins

A plugin is a self-contained directory under `{plugin_root}/{id}/` that ships features for the host application: Filament resources, models, migrations, assets, translations, hook listeners, page builder blocks, menu item sources, settings tabs, etc.

Where `{plugin_root}` = `base_path(config('mksine.plugins_path'))`. The default is `plugins/`.

## Why a plugin and not "just a service provider"

A plugin is a service provider plus:

- **Lifecycle.** Install / activate / deactivate / uninstall semantics with state persisted in `mks_plugins`.
- **Discovery.** No need to register in `config/app.php` or anywhere else; placing the directory and re-running `mks-plugin:discover` is enough.
- **Boot guard.** A failing plugin doesn't crash the application; it gets quarantined.
- **Asset publishing.** Compiled assets land under `public/plugins/{id}/` via a single command.
- **Migration scoping.** Plugin migrations live in the plugin tree; `mks-plugin:migrate {id}` runs only those.
- **Conventions.** Filament resources, pages, widgets are picked up automatically from canonical paths.

If you only need a single service binding, write a service provider in your app. The plugin system pays off when you have a feature that bundles UI + data + assets + lifecycle.

## The shape of a plugin

```
{plugin_root}/{id}/
├── plugin.php                 # manifest (id, name, version, plugin_class)
├── composer.json              # plugin-local dependencies (optional)
├── package.json               # build scripts (optional)
├── src/
│   ├── {Id}Plugin.php         # implements PluginInterface (lifecycle methods)
│   ├── Models/
│   ├── Filament/
│   │   ├── Resources/
│   │   ├── Pages/
│   │   └── Widgets/
│   └── Hooks/Listeners/       # discovery hooks live here
├── database/
│   └── migrations/
├── resources/
│   ├── lang/{locale}/
│   ├── views/
│   └── dist/                  # compiled assets, published to public/plugins/{id}/
└── routes/                    # web.php, api.php (optional)
```

Every directory is optional except `plugin.php`. The framework reads the manifest, then asks the plugin class about each capability.

## Lifecycle

A plugin moves through these states:

```
discovered  →  installed  →  active  ⇄  inactive  →  uninstalled
                                  │
                                  └─ failed (boot error; quarantined)
```

| State        | What happened                                                                 |
| ------------ | ----------------------------------------------------------------------------- |
| Discovered   | The directory exists and `mks-plugin:discover` parsed `plugin.php`.            |
| Installed    | `install()` ran successfully (creates DB row, runs migrations if any).        |
| Active       | `activate()` ran. The plugin's `boot()` runs on every request from now on.    |
| Inactive     | `deactivate()` ran. `boot()` no longer runs; data is preserved.                |
| Uninstalled  | `uninstall($deleteData)` ran; row removed from `mks_plugins`.                  |
| Failed       | `boot()` threw; the boot guard quarantines the plugin to keep the app alive.  |

See [Lifecycle](../guides/plugins/lifecycle.md) for the detailed contract and the boot guard mechanics.

## What "boot" means for a plugin

`PluginInterface::boot()` runs after the package's own service provider. Use it for:

- Registering hook listeners (runtime ones; discovery listeners self-register via the DB).
- Registering page builder blocks, menu item sources, menu locations, settings tabs.
- Setting config overrides (e.g. `mksine.user_model` for a plugin that ships a user subclass).
- Registering Filament panel plugins via `RegistersFilamentPlugins`.

What `boot()` should not do:

- Heavy I/O or DB queries (this runs on every request).
- Filesystem writes.
- Anything that can throw without being caught — the boot guard handles thrown exceptions, but not all of them safely.

## What plugins can and can't do to each other

Plugins are not isolated processes; they share the Laravel container. They can reach into each other if they want to. The framework provides:

- **Plugin-to-plugin API**: a contract (`PluginApiInterface`) for exposing typed entry points other plugins can consume. See [Plugin API](../guides/plugins/plugin-api.md).
- **Hooks**: the canonical extension surface — extend without coupling.

Plugins **cannot** safely:

- Modify another plugin's database schema.
- Override another plugin's resources by name without using hooks/contracts.
- Assume another plugin is active. Always check.

## How discovery works

`mks-plugin:discover`:

1. Scans `{plugin_root}/*/plugin.php`.
2. Builds a manifest registry (`PluginManifest`).
3. Caches the result at `bootstrap/cache/mks_plugins_discovery.php`.

Subsequent boots skip the scan; they read the cache. Use `--clear` after adding/renaming/removing plugins.

The boot sequence then registers each plugin's PSR-4 namespace via `PluginAutoloader` so plugin classes resolve before any plugin code runs.

## What "active" really means in practice

Active plugins have:

- Their `boot()` invoked on every request.
- Their Filament resources/pages/widgets discoverable by the panel.
- Their migrations runnable via `mks-plugin:migrate {id}`.
- Their hooks listed in `mks_hooks` (after `mks:discover`).

Deactivating a plugin:

- Stops calling `boot()`.
- Hides its Filament UI.
- **Does not** drop tables, delete data, or remove hooks from the DB. They remain available if reactivated.

Uninstalling with `--delete-data` is the only way to wipe plugin data. Without that flag, data persists.

## Where to read next

- Build one end to end: [Golden path](../guides/plugins/golden-path.md).
- Lifecycle in detail: [Lifecycle](../guides/plugins/lifecycle.md).
- Resources and pages: [Filament resources](../guides/plugins/filament-resources.md), [Filament pages and widgets](../guides/plugins/filament-pages-widgets.md).
- Cross-plugin extension: [Plugin API](../guides/plugins/plugin-api.md).
