---
title: Commands reference
description: Every Artisan command shipped by miran/mksine, with arguments, options, exit codes, and examples.
order: 4
---

# Commands reference

All commands below are registered by `Miran\Mksine\MksineServiceProvider`. Run them from the **application root** (the directory that contains `artisan`).

> **Convention.** `{plugin_root}` = `base_path(config('mksine.plugins_path'))`. The default is `plugins/`. See [Introduction](../00-introduction.md#convention-plugin_root).

> **Exit codes.** All commands follow Laravel’s convention: `0` = `Command::SUCCESS`, `1` = `Command::FAILURE`. Anything that prompts (`mks-plugin:uninstall`, `mksine:fresh-super-admin`) returns success after a confirmed cancel.

## Conventions used in this page

| Notation | Meaning |
|----------|---------|
| `{name}` | Required argument. |
| `{name?}` | Optional argument. |
| `--flag` | Boolean option (no value). |
| `--key=` | Option that takes a value. |

## Package commands (host app)

These come from `src/Commands/` and are registered via `Spatie\LaravelPackageTools\PackageServiceProvider::hasInstallCommand()` and `hasCommand()`.

### `mksine:install`

```
mksine:install [--migrate] [--force]
```

Source: [`MksineInstallCommand`](../../src/Commands/MksineInstallCommand.php).

What it does:

1. Copies `Miran\Mksine\Models\User` to `app/Models/User.php` (skips when present unless `--force`). The namespace is rewritten to `App\Models`.
2. Publishes the package config (`config/mksine.php`), migrations, translations, and fonts under their respective `vendor:publish` tags (`mksine-config`, `mksine-migrations`, `mksine-lang`, `mksine-fonts`).
3. With `--migrate`, runs `php artisan migrate` afterwards.

Options:

| Option | Default | Behaviour |
|--------|---------|-----------|
| `--migrate` | off | Run migrations after publishing. |
| `--force` | off | Pass `--force` to each `vendor:publish` and overwrite the `User` model if present. |

Use it:

```bash
php artisan mksine:install --migrate
```

### `mksine:info`

```
mksine:info
```

Source: [`MksineCommand`](../../src/Commands/MksineCommand.php).

Prints `mksine.version` and the toggles in `mksine.features`.

### `mksine:fresh-super-admin`

```
mksine:fresh-super-admin
  [--name=]
  [--email=]
  [--password=]
  [--panel=admin]
  [--force]
  [--export=]
  [--no-export]
```

Source: [`FreshSuperAdminCommand`](../../src/Console/Commands/FreshSuperAdminCommand.php).

Resets the **isolated** `mksine_setup` database connection (drops all tables, runs migrations against it), creates one `super_admin` user, and writes a portable database file plus a credentials file at the project root.

Critical safety properties (verified in code):

- Refuses to run when [Filament Shield tenancy](https://filamentphp.com/plugins/bezhan-shield) is enabled (no tenant assignment is possible).
- Validates that `MKSINE_SETUP_DB_*` is configured and that the setup connection is **not** the same database as the app default (separate file for SQLite, separate name on the same host for MySQL/MariaDB).
- Exports as `.sqlite` (SQLite) or `.sql` (MySQL/MariaDB via `mysqldump`); set `MYSQLDUMP_PATH` to override the binary.
- Credentials are written to `mksine-fresh-super-admin.txt` at the project root. **Add it to `.gitignore`** in your host app.

Use `--force` only in CI/scripts where you have already taken backups.

## Plugin commands

All plugin commands live under `src/Console/Commands/` and are registered with the `mks-plugin:` prefix.

### `mks-plugin:discover`

```
mks-plugin:discover [--clear]
```

Source: [`PluginDiscoverCommand`](../../src/Console/Commands/PluginDiscoverCommand.php).

Scans `{plugin_root}/*/plugin.php`, resolves PSR-4 autoload, parses every manifest, and warms the discovery cache. `--clear` invalidates the cache before re-scanning.

Run after:

- Adding or removing a plugin directory.
- Editing `plugin.php` (manifest changes).
- Pulling a deployment that ships new plugins.

### `mks-plugin:list`

```
mks-plugin:list [--status=]
```

Source: [`PluginListCommand`](../../src/Console/Commands/PluginListCommand.php).

Lists every discovered plugin with its status. Filter with `--status=active|inactive|installed|not_installed`.

### `mks-plugin:make`

```
mks-plugin:make {name}
  [--namespace=]
  [--author=]
  [--description=]
```

Source: [`PluginMakeCommand`](../../src/Console/Commands/PluginMakeCommand.php).

Scaffolds a new plugin under `base_path('plugins/' . $name)`. The full directory layout, `plugin.php`, the `PluginInterface` implementation, `composer.json`, route stubs, NPM/Vite config, and `publishes/README.md` are generated.

> **Caveat.** The output path is **hardcoded** to `base_path('plugins/' . $name)` and does **not** respect `mksine.plugins_path`. If you have customised the path, move the scaffold manually after generation and run `mks-plugin:discover`.

| Option | Default | Behaviour |
|--------|---------|-----------|
| `--namespace=` | `Studly($name)` | PHP root namespace for the plugin. |
| `--author=` | `Unknown` | Free-form author string written into `plugin.php` and `composer.json`. |
| `--description=` | `A custom MKS CMS plugin` | Description string. |

The `name` argument **must** match `^[a-z0-9\-]+$`. The command auto-runs discovery after creation.

### `mks-plugin:install`

```
mks-plugin:install {plugin}
```

Source: [`PluginInstallCommand`](../../src/Console/Commands/PluginInstallCommand.php).

Calls `PluginManager::install()` for the given plugin id: writes a row in `mks_plugins`, runs `migrate` against `migrationsPath()`, and invokes the manifest’s `install()` lifecycle hook.

Idempotent — running it on an already-installed plugin returns success.

### `mks-plugin:activate`

```
mks-plugin:activate {plugin}
```

Source: [`PluginActivateCommand`](../../src/Console/Commands/PluginActivateCommand.php).

Activates the plugin (auto-installs if needed) and copies `resources/dist/` to `public/plugins/{id}/` via `PluginManifest::publishAssets()`. If the plugin previously raised `boot_failed`, the command prompts before retrying.

### `mks-plugin:deactivate`

```
mks-plugin:deactivate {plugin}
```

Source: [`PluginDeactivateCommand`](../../src/Console/Commands/PluginDeactivateCommand.php).

Marks the plugin inactive. Database tables, files, and published assets are kept.

### `mks-plugin:uninstall`

```
mks-plugin:uninstall {plugin} [--delete-data]
```

Source: [`PluginUninstallCommand`](../../src/Console/Commands/PluginUninstallCommand.php).

Removes the plugin row from `mks_plugins` and invokes the manifest’s `uninstall($deleteData)`. With `--delete-data`, two confirmations are required and the plugin is expected to drop its tables/files itself.

### `mks-plugin:migrate`

```
mks-plugin:migrate {plugin?}
```

Source: [`PluginMigrateCommand`](../../src/Console/Commands/PluginMigrateCommand.php).

Runs pending migrations under each installed plugin’s `migrationsPath()`. Omit the argument to migrate **all** installed plugins in dependency order.

### `mks-plugin:publish`

```
mks-plugin:publish {plugin?} [--force]
```

Source: [`PluginPublishCommand`](../../src/Console/Commands/PluginPublishCommand.php).

Copies `{plugin_root}/{id}/resources/dist/` to `public/plugins/{id}/`. Add `--force` to overwrite. With no argument, every active plugin is published.

### `mks-plugin:publish-lang`

```
mks-plugin:publish-lang {plugin?}
```

Source: [`PluginPublishLangCommand`](../../src/Console/Commands/PluginPublishLangCommand.php).

Copies plugin translations into `lang/vendor/{id}/`. Always overwrites — your overrides should live in user-managed locations.

### `mks-plugin:make-model`

```
mks-plugin:make-model {plugin} {name} [-m|--migration]
```

Source: [`PluginMakeModelCommand`](../../src/Console/Commands/PluginMakeModelCommand.php).

Creates `src/Models/{Name}.php` inside the plugin. Table name follows the convention `mks_{plugin_id_with_underscores}_{snake_plural(name)}` — e.g. `my-shop` + `Product` → `mks_my_shop_products`.

`-m` adds a matching `Schema::create()` migration in `database/migrations/`.

### `mks-plugin:make-resource`

```
mks-plugin:make-resource {plugin} {name} [--model=]
```

Source: [`PluginMakeResourceCommand`](../../src/Console/Commands/PluginMakeResourceCommand.php).

Scaffolds a Filament v4 resource:

- `src/Filament/Resources/{Name}Resource/{Name}Resource.php`
- `Schemas/{Name}Form.php` (already wired through `FormHookManager::apply('{name}.form', …)`)
- `Tables/{Name}Table.php` (wired through `TableHookManager::apply('{name}.table', …)`)
- `Pages/List{Plural}.php` (extends `Miran\Mksine\Filament\Resources\Pages\MksineListRecords` to receive resource hooks)
- `Pages/Create{Name}.php`, `Pages/Edit{Name}.php`

`--model=` overrides the default `App\Models\{Name}` lookup; otherwise the resource imports `{namespace}\Models\{Name}`.

### `mks-plugin:make-page`

```
mks-plugin:make-page {plugin} {name}
```

Source: [`PluginMakePageCommand`](../../src/Console/Commands/PluginMakePageCommand.php).

Generates a Filament page class plus its Blade view in the plugin’s `src/Filament/Pages/` and `resources/views/filament/pages/`.

### `mks-plugin:make-widget`

```
mks-plugin:make-widget {plugin} {name} [--chart] [--stats]
```

Source: [`PluginMakeWidgetCommand`](../../src/Console/Commands/PluginMakeWidgetCommand.php).

Scaffolds one of three widget shapes inside the plugin:

| Flag | Result |
|------|--------|
| _(none)_ | Basic widget extending `Filament\Widgets\Widget`, with a Blade view at `resources/views/filament/widgets/{kebab-name}.blade.php`. |
| `--chart` | Extends `ChartWidget` with a sample dataset and `getType(): 'line'`. |
| `--stats` | Extends `StatsOverviewWidget` with three sample stats. |

`--chart` and `--stats` are mutually exclusive; if both are passed, `--chart` wins.

## Hook commands

### `mks:discover`

```
mks:discover
```

Source: [`DiscoverHooksCommand`](../../src/Console/Commands/DiscoverHooksCommand.php).

Scans the configured `mksine.hooks.discovery_paths` for classes implementing the listener interfaces and reconciles them with `mks_hooks`. New listeners are inserted; missing classes can be marked `is_orphaned = true` (depending on your configuration). System listeners (`is_system`) are never disabled.

Re-run after:

- Editing `mksine.hooks.discovery_paths`.
- Adding or removing listener classes.
- Pulling a deployment.

See [Hook discovery paths](../guides/hooks/discovery-paths.md).

## Theme commands

### `mks:make-theme`

```
mks:make-theme {name}
  [--identifier=]
  [--author=]
  [--description=]
  [--force]
```

Source: [`ThemeMakeCommand`](../../src/Console/Commands/ThemeMakeCommand.php).

Scaffolds a project theme under `resources/views/themes/{identifier}` with `theme.json`, `layouts/index.blade.php`, asset stubs, and a `vite.config.js`.

| Option | Default | Behaviour |
|--------|---------|-----------|
| `--identifier=` | `Str::slug($name)` | Filesystem identifier (also used as the view namespace `theme::{id}`). |
| `--author=` | _empty_ | Author string for `theme.json`. |
| `--description=` | `A custom theme for MKSine` | Description string. |
| `--force` | off | Overwrite an existing theme directory. |

### `mks:theme-publish`

```
mks:theme-publish {theme?}
```

Source: [`ThemePublishCommand`](../../src/Console/Commands/ThemePublishCommand.php).

Copies a theme’s `dist/`, `images/`, and `screenshot` into the right public location:

- Project themes → `public/themes/{id}/`
- Package themes → `public/vendor/mksine/themes/{id}/`

Omit the argument to publish every discovered theme.

### `mks:theme-publish-lang`

```
mks:theme-publish-lang {theme?}
```

Source: [`ThemePublishLangCommand`](../../src/Console/Commands/ThemePublishLangCommand.php).

Copies a theme’s `resources/lang/` (or `lang/`) into `lang/vendor/theme-{id}/`. Always overwrites the destination.

## Release / build commands

### `mks:release-archive`

```
mks:release-archive
  [--output=]
  [--skip-build]
  [--dry-run]
```

Source: [`ReleaseArchiveCommand`](../../src/Console/Commands/ReleaseArchiveCommand.php).

Discovers every `package.json` with a `build` script under the project (including `packages/`, `themes/`, `{plugin_root}/*`, and the project root), runs `npm run build` in each, then zips the project.

The archive **excludes** `node_modules`, `.git`, and `.env*` (keeping `.env.example`). Inside `public/`, only an explicit allowlist is included: `build`, `themes`, `vendor/mksine`, `css`, `js`, `fonts`, `plugins`, plus `index.php`, `.htaccess`, `robots.txt`, `favicon*`. Everything else under `public/` is omitted.

| Option | Default | Behaviour |
|--------|---------|-----------|
| `--output=` | `mksine-release-{timestamp}.zip` at project root | Absolute or project-relative output path. |
| `--skip-build` | off | Skip every `npm run build`; just zip what is on disk. |
| `--dry-run` | off | Print the build roots and resolved output path; no work happens. |

See [Operations → release archive](../operations/deployment-hosting.md) for the deployment flow.

## Updater commands

See [Operations → ZIP updater](../operations/zip-updater.md) for the full pipeline description. All updater commands require Super Admin permission and honour `config('mksine.updater.enabled')`.

### `mks-plugin:update`

```
mks-plugin:update {plugin} {file} [--force]
```

Source: [`UpdatePluginCommand`](../../src/Console/Commands/UpdatePluginCommand.php).

Update a **project** plugin (`plugins/{id}`) from a ZIP. The ZIP's `plugin.php` must declare the same `id` as the target and a strictly higher `version` (unless `--force` is passed).

Pipeline: validate → extract → deactivate old → atomic swap → publish-lang → publish assets → discover → `optimize:clear` → migrate last. On migration failure the plugin is marked `boot_failed=true` + `status=inactive` and the new code stays in place; see the operations doc for recovery.

### `mks:theme-update`

```
mks:theme-update {theme} {file} [--force]
```

Source: [`UpdateThemeCommand`](../../src/Console/Commands/UpdateThemeCommand.php).

Update a **project** theme (`resources/views/themes/{id}`) from a ZIP. The ZIP must contain `theme.json`, a higher `version`, and a `dist/` directory (production has no npm to build).

### `mksine:update`

```
mksine:update {file} [--force]
```

Source: [`UpdateCoreCommand`](../../src/Console/Commands/UpdateCoreCommand.php).

Update the core `miran/mksine` package in path-repository installs (`packages/mksine/`). Rejected if:

- the ZIP's `composer.json` does not declare `"name": "miran/mksine"`,
- the ZIP changes any entry in `require` or `require-dev` (production has no composer),
- the new version is not strictly higher (override with `--force`).

### `mks-plugin:rollback` / `mks:theme-rollback` / `mksine:rollback`

```
mks-plugin:rollback {plugin}
mks:theme-rollback {theme}
mksine:rollback
```

Sources: [`RollbackPluginCommand`](../../src/Console/Commands/RollbackPluginCommand.php), [`RollbackThemeCommand`](../../src/Console/Commands/RollbackThemeCommand.php), [`RollbackCoreCommand`](../../src/Console/Commands/RollbackCoreCommand.php).

Restore the most recent backup in `{target-parent}/.mks-backups/`. **Code-only** rollback — migrations are **not** reversed. Combine with a DB snapshot restore if needed.

## Typical sequences

**Onboarding a brand-new plugin:**

```bash
php artisan mks-plugin:make my-shop --namespace=MyShop
php artisan mks-plugin:discover
php artisan mks-plugin:install my-shop
php artisan mks-plugin:activate my-shop
php artisan mks-plugin:migrate my-shop
```

**After adding hook listener classes:**

```bash
# Optional — only if you store listeners outside the defaults
php artisan config:cache

php artisan mks:discover
```

**Shipping plugin assets to production:**

```bash
cd {plugin_root}/my-shop && npm install && npm run build
cd -
php artisan mks-plugin:publish my-shop --force
php artisan mks-plugin:publish-lang my-shop
```

**Building a deployable archive:**

```bash
php artisan mks:release-archive --dry-run         # verify build roots
php artisan mks:release-archive                   # builds + zips
```

## See also

- [Configuration](configuration.md) — every config key consumed by these commands.
- [Plugin lifecycle](../guides/plugins/lifecycle.md) — what `install/activate/deactivate/uninstall` actually call.
- [Operations: deployment & hosting](../operations/deployment-hosting.md) — using `mks:release-archive` end-to-end.
