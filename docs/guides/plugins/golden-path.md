---
title: Plugin golden path
description: End-to-end path from "no plugin" to a working Filament resource, with built-and-published assets.
order: 10
---

# Plugin golden path

Run every command from the **Laravel application root** (the directory containing `artisan`). Throughout this page, **`{plugin_root}`** stands for `base_path(config('mksine.plugins_path'))` (default: `plugins/`).

> Looking for the underlying contracts? See [`PluginInterface`](../../reference/contracts.md#plugininterface), [`PluginManifest`](../../../src/Core/Plugins/PluginManifest.php), and [`PluginManager`](../../../src/Core/Plugins/PluginManager.php).

## 0. Preconditions

- `miran/mksine` is installed: Filament panel + `MksinePlugin` registered, then [`mksine:install --migrate`](../../reference/commands.md#mksineinstall) (which runs `shield:generate --all` when the panel is ready), and a super admin exists (`mksine:create-super-admin` or install flags). Tables `mks_plugins`, `mks_hooks`, `permissions`, etc. must exist.
- The Filament admin panel boots and you have a Super Admin (Shield).
- `mksine.features.plugin_system` is `true` (the default).

If any of those is missing, fix them first — the plugin lifecycle expects them.

## 1. Scaffold

```bash
php artisan mks-plugin:make my-plugin \
  --namespace=MyPlugin \
  --author="Your Name" \
  --description="What this plugin does."
```

Constraints baked into [`PluginMakeCommand`](../../../src/Console/Commands/PluginMakeCommand.php):

- `name` must match `^[a-z0-9\-]+$`.
- The output path is hardcoded to `base_path('plugins/' . $name)` — if your `mksine.plugins_path` is custom, move the directory after generation.
- A complete tree is created (`src/`, `database/migrations/`, `routes/`, `resources/{css,js,dist,views,lang}`, `publishes/`, `composer.json`, `package.json`, `vite.config.js`).

The generator runs `mks-plugin:discover` automatically at the end.

## 2. Inspect the manifest

`{plugin_root}/my-plugin/plugin.php` is the **source of truth** for the plugin. The minimum:

```php
return [
    'id'          => 'my-plugin',
    'name'        => 'My Plugin',
    'version'     => '1.0.0',
    'description' => 'What this plugin does.',
    'author'      => 'Your Name',
    'requires'    => ['mksine' => '^1.0'],
    'namespace'   => 'MyPlugin',
    'plugin_class'=> 'MyPlugin\\MyPluginPlugin',
    'autoload'    => ['MyPlugin\\' => 'src/'],
    'hooks'       => [
        'public'  => [],
        'private' => [],
    ],
];
```

`id`, `name`, and `version` are required. `id` must match the directory name and be unique across all plugins. See [Lifecycle](lifecycle.md) for what each field actually drives.

## 3. Discover

```bash
php artisan mks-plugin:discover --clear
```

`mks-plugin:discover` parses every manifest, registers PSR-4 from `autoload`, and warms the discovery cache. Use `--clear` after editing `plugin.php`. Whether `bootstrap/cache/mks_plugins_discovery.php` is committed is a team decision; committing it makes deploys hermetic.

## 4. Install + activate

```bash
php artisan mks-plugin:install my-plugin
php artisan mks-plugin:activate my-plugin
```

What happens (see [`PluginLifecycle`](../../../src/Core/Plugins/PluginLifecycle.php)):

1. `install` — inserts `mks_plugins` row, runs migrations under `database/migrations/`, calls `PluginInterface::install()`.
2. `activate` — flips status, calls `PluginInterface::activate()`, publishes translations to `lang/vendor/{id}/`, and on next request calls `boot()` and registers routes.

If `boot()` throws, the plugin is auto-disabled and `mks_plugins.boot_failed` becomes `1`. See [Troubleshooting](../../operations/troubleshooting.md#plugin-boot-failed).

## 5. Add a model + migration

```bash
php artisan mks-plugin:make-model my-plugin Item --migration
php artisan mks-plugin:migrate my-plugin
```

Table-name convention (enforced by [`PluginMakeModelCommand`](../../../src/Console/Commands/PluginMakeModelCommand.php)): `mks_{plugin_id_underscored}_{snake_plural(name)}`. Example: `my-plugin` + `Item` → `mks_my_plugin_items`. Override by editing the generated migration before running it.

## 6. Add a Filament resource

```bash
php artisan mks-plugin:make-resource my-plugin Item --model=Item
```

What you get (`src/Filament/Resources/ItemResource/`):

- `ItemResource.php` — top-level resource class.
- `Schemas/ItemForm.php` — form schema, **already wrapped** by `FormHookManager::apply('Item.form', …)`.
- `Tables/ItemTable.php` — table schema, wrapped by `TableHookManager::apply('Item.table', …)`.
- `Pages/{ListItems,CreateItem,EditItem}.php` — `ListItems` extends `Miran\Mksine\Filament\Resources\Pages\MksineListRecords` so resource hooks apply.

Permissions: generate via Shield as you would for any Filament resource (`shield:generate`).

## 7. Build + publish frontend assets

From the plugin directory:

```bash
cd {plugin_root}/my-plugin
npm install
npm run build
```

The default `package.json` runs `vite build`, then chains `mks-plugin:publish my-plugin` and the Filament/MKSine asset sync scripts. From the application root:

```bash
php artisan mks-plugin:publish my-plugin --force
```

This copies `resources/dist/` → `public/plugins/my-plugin/`. See [Assets and Vite](assets-vite.md) for the rules around Tailwind preflight and Filament collisions.

## 8. Translations

If you ship localised strings (e.g. `resources/lang/en/messages.php`):

```bash
php artisan mks-plugin:publish-lang my-plugin
```

Translations land in `lang/vendor/my-plugin/` and **always overwrite**. See [Translations](translations.md).

## 9. Class-based hook listeners

If your plugin defines listeners (anything implementing `MksineListenerInterface`, `FormHookListenerInterface`, `TableHookListenerInterface`):

1. Add the directory to `mksine.hooks.discovery_paths`:

   ```php
   'discovery_paths' => [
       base_path(config('mksine.plugins_path').'/my-plugin/src/Hooks/Listeners'),
   ],
   ```

2. Run:

   ```bash
   php artisan mks:discover
   ```

See [Hooks → Discovery paths](../hooks/discovery-paths.md).

## 10. Commit policy

Track in version control:

- `{plugin_root}/my-plugin/**` — all source, including `plugin.php`, `composer.json`, `package.json`, and `resources/dist/` (so production never needs `npm`).
- `public/plugins/my-plugin/**` — committed only if you do **not** run `mks-plugin:publish` on deploy.
- `lang/vendor/my-plugin/**` — committed only if you do **not** run `mks-plugin:publish-lang` on deploy.

For most teams, committing built assets is the simplest, lowest-risk option. See [ADR 002](../../adr/002-committed-plugin-assets.md).

## What can go wrong

| Symptom | Probable cause | Fix |
|---------|---------------|-----|
| `Plugin not found` after `make` | Discovery cache stale | `php artisan mks-plugin:discover --clear` |
| `Plugin already installed` | Trying to install twice | Run `mks-plugin:activate` instead |
| `boot_failed = 1`, plugin disabled | Exception in `boot()` | Check `storage/logs/laravel.log`, fix, then `mks-plugin:activate` again |
| Filament UI broken after `mks-plugin:publish` | Plugin CSS overrides Filament | Scope Tailwind to plugin selectors, do **not** ship preflight that overrides root styles |
| Resource menu missing | Permissions not generated | `php artisan shield:generate` and re-assign roles |

See [Troubleshooting](../../operations/troubleshooting.md) for the full diagnostic table.

## Next steps

- [Lifecycle](lifecycle.md) — exact semantics of `install/activate/deactivate/uninstall/boot`.
- [Models and migrations](models-migrations.md) — table conventions, dependent migrations.
- [Filament resources](filament-resources.md) — how the generated form/table hook into MKSine.
- [Plugin-to-plugin API](plugin-api.md) — exposing services to other plugins safely.
