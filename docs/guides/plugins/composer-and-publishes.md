---
title: Composer and publishes presets
description: Vendoring third-party packages inside a plugin and using publishes/ JSON recipes to materialise their assets.
order: 17
---

# Composer and publishes presets

Plugins are self-contained Composer packages that live inside the host application. This page covers two concerns:

1. The plugin’s own `composer.json` and PSR-4 autoload (the basics).
2. The `publishes/` JSON-recipe system that lets a plugin materialise vendor assets (config files + migration stubs) into its own tree without relying on the host’s `vendor:publish`.

## Plugin `composer.json`

The scaffolded file:

```json
{
    "name": "mks-plugins/{plugin-id}",
    "description": "...",
    "type": "mks-plugin",
    "license": "MIT",
    "require": {
        "php": "^8.2"
    },
    "autoload": {
        "psr-4": {
            "{Namespace}\\": "src/"
        }
    },
    "extra": {
        "mks-plugin": {
            "class": "{Namespace}\\{StudlyId}Plugin"
        }
    }
}
```

Key points:

- `type` is `mks-plugin` — descriptive only, no Composer plugin behaviour.
- `extra.mks-plugin.class` is informational; runtime resolution goes through `plugin.php`’s `plugin_class` field.
- The host autoload **does not** automatically include this `composer.json`. MKSine uses its own [`PluginAutoloader`](../../../src/Core/Plugins/PluginAutoloader.php) which reads the `autoload` block in `plugin.php` and registers PSR-4 against `src/` (or whatever you set).

You only need a real `composer install` inside the plugin if the plugin **itself** depends on third-party packages (see next section).

## Vendoring third-party packages

When your plugin pulls in (say) `spatie/laravel-activitylog`, you have two options:

### Option A — host-level Composer dependency

Add the package to the **host** `composer.json` and use it directly. Simplest, no autoload tricks. The trade-off: every host project that installs your plugin must also install the dependency.

### Option B — plugin-local `vendor/` (committed)

Run `composer require` **inside the plugin directory** so the package lands under `{plugin}/vendor/{vendor}/{name}/`. Commit it. Pros:

- Plugin is fully self-contained; the host doesn’t need to know.
- Works for ZIP deploys where the host can’t run Composer.

Cons:

- Plugins that ship full `vendor/` trees can collide with the host’s versions of the same package. **Do not** require the plugin’s `vendor/autoload.php` from `boot()` — it duplicates Symfony/Laravel and breaks the host autoload order.
- Larger commits.

MKSine special-cases two well-known packages and registers their PSR-4 manually if vendored under a plugin (see [`PluginManager::registerPluginActivitylogAutoload()`](../../../src/Core/Plugins/PluginManager.php) and `registerPluginRmsramosActivitylogAutoload()`):

- `spatie/laravel-activitylog`
- `rmsramos/activitylog`

For any other package you vendor, register the PSR-4 in your plugin’s `boot()`:

```php
public function boot(): void
{
    $loader = new \Composer\Autoload\ClassLoader();
    $loader->addPsr4('Vendor\\Package\\', __DIR__ . '/../vendor/vendor/package/src');
    $loader->register(false);
}
```

Wrap with `is_dir()` so the plugin still boots when the directory is missing.

## `publishes/` — JSON publish recipes

The challenge: a vendor package ships migrations or config under `vendor/{pkg}/database/migrations/` or `vendor/{pkg}/config/foo.php`, but the host can’t use `php artisan vendor:publish` to copy them out (the package isn’t registered against the host). You want those files **inside the plugin** so they are versioned, customisable, and migrate on `mks-plugin:migrate`.

[`PluginVendorPublishRunner`](../../../src/Core/Plugins/Publishing/PluginVendorPublishRunner.php) solves this with a recipe file per "preset" under `{plugin}/publishes/{preset}.json`:

```json
{
    "vendor_path": "spatie/laravel-activitylog",
    "config": {
        "from": "config/activitylog.php",
        "to": "config/activitylog.php"
    },
    "migrations": [
        "create_activity_log_table",
        "add_event_column_to_activity_log_table"
    ]
}
```

Field semantics:

- `vendor_path` (required, string): path under `{plugin}/vendor/`. Must exist after `composer require` inside the plugin.
- `config.from` / `config.to`: paths inside the package and inside the plugin tree. The destination directory is created if missing. Existing destinations are skipped unless you pass `--force` to your runner.
- `migrations`: array of base names (no timestamp, no `.php`). The runner finds `database/migrations/{name}.php` (or `.php.stub`) inside the package, then writes them under `{plugin}/database/migrations/{Y_m_d_His}_{snake_name}.php`.

If a destination migration with the same base name already exists, the runner **skips** it — no duplicates, no clobbering.

### Wiring it into your plugin

There is **no global Artisan command** for this. The deliberate choice (see [ADR 004](../../adr/004-no-core-vendor-publish-command.md)) is: every plugin owns the command name and decides when to run it. Inside your plugin’s `boot()`:

```php
use Illuminate\Support\Facades\Artisan;
use Miran\Mksine\Core\Plugins\Publishing\PluginVendorPublishRunner;
use Symfony\Component\Console\Style\SymfonyStyle;

public function boot(): void
{
    if (! app()->runningInConsole()) {
        return;
    }

    Artisan::command('my-plugin:publish-vendor {preset} {--force}', function () {
        /** @var \Illuminate\Console\Command $this */
        $manager = app(\Miran\Mksine\Core\Plugins\PluginManager::class);
        $manifest = $manager->getManifest('my-plugin');
        $runner = app(PluginVendorPublishRunner::class);

        $io = new SymfonyStyle($this->input, $this->output);

        return $runner->publish($manifest, $this->argument('preset'), $io, (bool) $this->option('force'));
    })->purpose('Materialise vendor assets into my-plugin/');
}
```

Then from the app root:

```bash
php artisan my-plugin:publish-vendor activitylog
php artisan my-plugin:publish-vendor activitylog --force
php artisan mks-plugin:migrate my-plugin
```

`PluginVendorPublishRunner::listPresets($manifest)` returns the list of preset filenames (minus `.json`). Use it to expose `--list` flags or interactive prompts.

## Why JSON, not PHP?

JSON is intentionally inert:

- A plugin user can read it without executing anything.
- Recipes can be linted in CI.
- Publishing the same recipe is deterministic and diff-friendly.

If you need conditional logic ("publish migration X only on MySQL"), put it in the runner command, not the recipe.

## Pitfalls

- **Running `composer install` inside the plugin in production**: don’t. Vendor everything into git or have the host pull it via `composer install` once at deploy. Plugins shouldn’t need network access at runtime.
- **Namespacing collisions** when both the host and a plugin vendor `spatie/laravel-activitylog`: pin the same major in both, or pick one to own.
- **Editing published config under the plugin** without committing it: the next `mks-plugin:publish-vendor --force` overwrites it. Treat `{plugin}/config/{file}.php` as the canonical, hand-edited copy.
- **Forgetting `mks-plugin:migrate`** after `publish-vendor`: published migrations don’t auto-run; the runner only copies them.

## See also

- [`PluginVendorPublishRunner` source](../../../src/Core/Plugins/Publishing/PluginVendorPublishRunner.php).
- [ADR 004 — no core vendor-publish command](../../adr/004-no-core-vendor-publish-command.md).
- [Models and migrations](models-migrations.md) — how the migrations behave once they’re inside the plugin.
