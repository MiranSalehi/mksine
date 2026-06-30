---
title: Troubleshooting
description: Common MKSine failures, their diagnosis, and fixes.
order: 2
---

# Troubleshooting

`{plugin_root}` = `base_path(config('mksine.plugins_path'))` — see [Introduction](../00-introduction.md).

This page collects the failures we see most often. For each one: the symptom, how to confirm the cause, and the fix.

## Installation and panel

### `Route [filament.admin.pages.mksine-dashboard] not defined`

**Symptom.** Visiting `/admin` returns HTTP 500 with a missing route for `filament.admin.pages.mksine-dashboard` (often in `sidebar.blade.php`).

**Common causes.**

1. **Duplicate dashboard pages (most common).** `filament:install --panels` adds `->pages([Dashboard::class])`. MKSine registers `MksineDashboard` on the same `/admin` URL. Only one route survives; navigation links to `mksine-dashboard` → 500. **Remove `Dashboard::class` from `AdminPanelProvider`** (see [Installation §3](../01-installation.md#3-register-the-filament-plugin)).
2. **Stale Filament component cache** (`bootstrap/cache/filament/panels/admin.php`) built before MKSine was added or with the duplicate dashboard layout.

**Fix.**

```bash
php artisan filament:optimize-clear
php artisan optimize:clear
composer update miran/mksine
```

Confirm `MksinePlugin::make()` is on the admin panel. Re-run `php artisan mksine:install --migrate` on fresh installs if needed.

### MKSine resources missing from Shield / super admin has no CMS permissions

**Symptom.** Super admin can log in but CMS menu items are hidden or return 403; `permissions` table has few or no `*_post`, `*_media`, etc. rows.

**Cause.** `shield:generate` ran **before** `MksinePlugin` was registered on the Filament panel (wrong install order), or was never run after enabling a new plugin.

**Fix.**

```bash
php artisan shield:generate --all --panel=admin
```

Re-assign roles if needed. Existing super admins pick up new permissions on the role after `syncPermissions` (log out/in to clear Spatie’s cache). See [Installation](../01-installation.md) for the correct order.

### `mksine:install` skipped Shield generation

**Symptom.** Installer prints “Skipping Shield generation — the admin panel is not ready.”

**Fix.** Follow the printed steps: run `filament:install --panels`, register `MksinePlugin::make()` in `AdminPanelProvider`, then either re-run `mksine:install --migrate` or run `shield:generate --all --panel=admin` manually.

### Admin styles missing / MKSine CSS

**Symptom.** Panel loads but looks like a bare Filament install: no MKSine sidebar groups, wrong fonts/spacing, or missing dark-mode utilities.

**Cause.** MKSine admin CSS (`mksine-styles`) was not published to `public/` or the panel did not load it. `mksine:install --migrate` runs `filament:assets`; skipping install or an outdated package can leave styles unloaded.

**Fix.**

```bash
php artisan filament:assets
php artisan view:clear
```

Hard-refresh the browser (`Cmd/Ctrl+Shift+R`). Confirm `vendor/miran/mksine/resources/dist/mksine.css` exists in your install (shipped with the package). After upgrading `miran/mksine`, run `filament:assets` again.

## Plugins

### Plugin not found by `mks-plugin:discover`

**Symptom.** A plugin folder exists at `{plugin_root}/{id}/` but `mks-plugin:list` doesn’t show it.

**Diagnose.**
- `php artisan mks-plugin:discover --clear` and re-list.
- Verify `{plugin_root}/{id}/plugin.php` exists and returns the manifest array (or registers a class).
- Check `id` matches the folder name (kebab-case, no spaces).
- Check the `bootstrap/cache/mks_plugins_discovery.php` cache file is writable and current.

**Fix.** Correct the manifest/folder name, ensure write permissions, run `mks-plugin:discover --clear`.

### Plugin boot failed

**Symptom.** The admin shows a plugin as installed but features are missing; or you see entries in `storage/logs/laravel.log`.

**Diagnose.**
- Read the trace from `laravel.log`.
- `select id, status, boot_error, last_booted_at from mks_plugins where id = '{id}'` — `boot_error` holds the exception message from the most recent failed boot.
- Common causes:
  - Wrong class name in `plugin.php` → `plugin_class`.
  - Plugin-local Composer dependencies not installed (`cd {plugin_root}/{id} && composer install`).
  - Container resolution failure inside the plugin's `boot()`.
  - Hard-coded paths that assume `plugins/` instead of `{plugin_root}`.

**Boot guard.** The boot-flag staleness window is `mksine.plugins.boot_guard_ttl` seconds (default `15`). Flags younger than the TTL are treated as "still booting" to keep concurrent requests safe; flags older than the TTL trigger boot-failure detection and the plugin is marked failed. Fix the underlying error, then re-activate from the admin (or `mks-plugin:activate {id}`).

### Plugin classes can't be autoloaded

**Symptom.** `Class not found` errors for plugin classes that you can clearly see in the filesystem.

**Diagnose.**
- The plugin `composer.json` PSR-4 prefix must match the actual namespaces under `src/`.
- The plugin's classmap is loaded by the `PluginAutoloader`; run `php artisan mks-plugin:discover --clear` after changing namespaces.
- If you symlinked the plugin from outside `{plugin_root}`, the autoloader resolves through the link target — make sure there is no stale `vendor/composer/autoload_*.php` cache from a different install.

**Fix.** Re-discover with `--clear`. If using a `path` Composer repository, run `composer dump-autoload` in the project root.

## Hooks

### Discovery hook listener never runs

**Symptom.** A class implementing `MksineListenerInterface` exists, but nothing happens when the event fires.

**Diagnose.**
- Confirm the class lives under a path scanned by `mks:discover`: package's `Core/Listeners` **or** any path in `config('mksine.hooks.discovery_paths')`.
- Run `php artisan mks:discover` after **every** code change that adds, removes, or renames a listener.
- `select * from mks_hooks where listener_class = '...'` — confirm the row exists and `is_enabled = 1`. System hooks always run regardless of `is_enabled`.

**Fix.** Add the discovery path; re-run `mks:discover`; flip `is_enabled` if it was disabled in the admin.

### Form or table hook not applied

**Symptom.** You registered a `FormHookListenerInterface` (or called `Hooks::extendForm()`), but the form on the resource doesn't change.

**Diagnose.**
- The hook **name** must match what the resource calls. Convention is `{model_singular_snake}.form` and `{model_singular_snake}.table`. Check the resource's `form()`/`table()` to see the literal string.
- Class-based listeners must be discovered (`mks:discover`); runtime registrations live only for the current request unless re-registered every boot.
- Form hooks **swallow exceptions** — check `laravel.log` for warnings starting with `[FormHook]` or `[TableHook]`.

**Fix.** Match the name; re-discover; wrap your callback’s body in a try/catch and log a meaningful message so silent failures aren’t silent.

### Async listener not running

**Symptom.** A listener that implements `QueueableHookEventInterface` runs synchronously (or not at all).

**Diagnose.**
- Confirm `php artisan queue:work` (or your supervisor configuration) targets the connection/queue from `mksine.hooks.queue.*`.
- Check the event class itself implements `QueueableHookEventInterface` and provides `toQueuePayload()` / `fromQueuePayload()`.
- Verify the listener returns `true` from `shouldQueue()`.
- Inspect `failed_jobs` for serialization or restoration errors.

**Fix.** See [Async and queues](../guides/hooks/async-and-queues.md). The four-condition rule there is canonical.

### Slow hooks logged but threshold ignored

The kernel does **not** currently honour `mksine.hooks.log_slow_hooks` or `slow_hook_threshold` in `HookDispatcher`. Use the per-listener instrumentation pattern in [Slow-hook logging](../guides/hooks/slow-hook-logging.md) until the dispatcher ships native timing.

## Auth and admin

### Admin 403 / cannot access the panel

**Diagnose.**
- The user model must implement Filament’s user contract and the Shield trait expected by your config.
- If a plugin replaced the user model, `auth.providers.users.model`, `mksine.user_model`, and `filament-shield.auth_provider_model` must all point at it. See [User subclass](../guides/auth/user-subclass.md).
- `select * from model_has_roles where model_id = {your_user_id}` — confirm the user has at least one role with the relevant permissions.

**Fix.** Run `php artisan shield:generate --all`, assign roles, and (if you replaced the user model) override `getMorphClass()` so Spatie role rows still resolve.

### Super admin can log in but cannot access resources

**Cause.** Permissions were never generated. `mksine:create-super-admin`, `mksine:fresh-super-admin`, and `shield:super-admin` all assign the super admin role, but the role only receives permission rows that already exist in `permissions` (via `syncPermissions`). Until `shield:generate --all` runs, that set is empty.

**Fix.** Run `php artisan shield:generate --all` after `mksine:install --migrate`; re-run after enabling new plugins or adding new Filament resources. Then re-run `mksine:create-super-admin` only if you need a **new** user — existing super admins pick up new permissions on the role automatically after sync (or log out/in to clear Spatie’s cache).

### `mksine:fresh-super-admin` succeeds but the user can do nothing

Same root cause as above on the **setup** database. Run `shield:generate --all` against that database (or import a dump that already includes generated permissions) before exporting.

## Themes

### Theme not appearing in the picker

**Diagnose.** `php artisan mks:theme-publish {theme}` and confirm `themes/{theme}/theme.json` is well-formed.

**Fix.** Re-publish; clear view cache (`php artisan view:clear`).

### "View not found" after activating a theme

**Diagnose.** The theme’s view namespace wasn’t registered. Confirm `ThemeBootstrap::boot()` ran — usually a stale config cache hides it.

**Fix.** `php artisan optimize:clear`.

### Theme custom CSS/JS doesn't update

**Cause.** The `dist/custom.css` and `dist/custom.js` URLs are streamed from `storage/app/theme-custom/`, not from `public/`. Browser cache busting depends on the `?v=` param emitted by `@themeAssets`.

**Fix.** Hard-reload (`Cmd/Ctrl+Shift+R`); confirm `storage/app/theme-custom/{theme}/` contains your file; verify `Theme settings → Custom CSS/JS` in the admin matches the file content.

## Page builder

### Block renders as a yellow "Unknown component type" placeholder

**Cause.** Either the block type is not registered with the `ComponentRegistry`, or the resolved render view doesn't exist.

**Diagnose.**
- `php artisan view:clear`.
- Confirm the plugin/theme that registers the block is active.
- Check `getRenderView()` returns a real view path (test with `View::exists($name)` in tinker).

### `PageBuilderField` not visible in the editor

**Cause.** `mksine.features.page_builder` is `false`.

**Fix.** Set `MKSINE_FEATURE_PAGE_BUILDER=true` in `.env` and clear config cache.

### Builder pages render but children don't

**Cause.** Container block view used the wrong shape (flat array vs. column buckets) or didn’t loop through `mksine::page-builder.render.block`.

**Fix.** Check the block’s `createInstance()` and the render view. See [Nesting](../guides/page-builder/nesting.md).

## Menus

### Menu Builder shows "no items" for a custom source

**Diagnose.**
- The source must be registered via `MenuItemSourceManager` in `boot()`.
- `getItemsPaginated()` (or `getItems()`) must return at least one row for the active filter.
- An exception thrown inside `getItemsPaginated()` may be swallowed by Filament's UI; check `laravel.log`.

### Frontend `MenuService::forLocation('foo')` returns `null`

**Cause.** Either the location isn’t in `menu_locations` (sync hasn’t run) or no menu is assigned to it.

**Fix.** Open `Menus → Menu locations` in the admin (which calls `syncToDatabase()`), or call `app(MenuLocationManager::class)->syncToDatabase()` from a service provider/seeder.

## Media

### Upload returns "The file may not be greater than X kilobytes"

**Cause.** Either `mksine.media.max_size` or the PHP-level limits (`upload_max_filesize`, `post_max_size`) are smaller than the file.

**Fix.** Raise both. The package does not override `php.ini`.

### `MediaPicker` doesn’t persist when the page saves

**Cause.** In `relation(true)` mode (default), `MediaPicker` writes `media_attachments` rows during `saveRelationships()`. If you bound the field name to a column that doesn't exist, Filament may complain at validation time but silently swallow the picker state.

**Fix.** Don’t add a model column for a relational picker; let it write to `media_attachments`.

### Old uploads still serve from a former disk after migration

**Cause.** The `media` row stores `disk` and `path` per-row. Changing `mksine.media.disk` only affects new uploads.

**Fix.** Migrate the files **and** update each row’s `disk`/`path` columns. Write a one-off Artisan command; the package doesn't ship one.

## Settings

### "Saved" but the value isn’t reflected

**Diagnose.**
- `mks_setting('key')` is per-request cached. Refresh the request or clear caches.
- `select value from settings where key = '...'` — is the value JSON-encoded? The page detects arrays on save and decodes valid JSON on read; a manually edited row that mixed shapes will look wrong.

### Permalink change didn't update routes

**Cause.** The `Settings` page calls `route:clear` only for keys belonging to the permalinks tab. Custom permalink-like keys you added in your own tab won't trigger the clear.

**Fix.** Either route them through the permalinks tab, or run `php artisan route:clear` in your save observer.

## Translations / Languages

### Plugin translations updated in admin but not visible in views

**Diagnose.**
- The admin writes the plugin source file *and* copies it to `lang/vendor/{plugin_id}/{locale}/`. Confirm both files exist and have your changes.
- Translator caches: `php artisan optimize:clear`.

**Fix.** Clear caches; double-check the namespace (`__('plugin-id::file.key')` not `__('plugins.plugin-id.file.key')`).

### Locale not appearing in the Languages page

**Cause.** `TranslationFileManager::getAvailableLocales()` only lists locales it can detect from `lang/{locale}/` directories or `lang/{locale}.json` files. New locales need a directory or JSON file to appear.

**Fix.** `app(TranslationFileManager::class)->addLocale('fr', copyFrom: 'en')` from a one-off command, or create the directory manually.

## Web server / hosting

### 404 for `/livewire/...` or other Laravel routes (no physical file)

**Diagnose.**
- **nginx (or proxy) 404:** the request never reached `public/index.php`. Set the document root to Laravel `public/` and add a front-controller rule (for example `try_files $uri $uri/ /index.php?$query_string`). `.htaccess` does **not** apply to nginx.
- **Wrong web root:** if the site root is the repository root instead of `public/`, virtual URLs such as `/livewire/livewire.js` do not exist on disk and will 404 unless rewritten.
- **`APP_DEBUG` vs script URL:** when `config('app.debug')` is false, Livewire serves `livewire.min.js` only; cached config or mixed environments can cause a Blade output / served URL mismatch. After changing `.env`, run `php artisan config:clear` (or rebuild config cache intentionally).

**Fix.** See [Deployment and hosting](deployment-hosting.md) for the canonical server config.

### Storage symlink missing on shared hosting

**Symptom.** Uploaded media returns 404 from `/storage/...`.

**Fix.** `php artisan storage:link`. On hosts that disallow symlinks, configure the disk to write directly to a public directory or front it with a route.

## Assets

### Filament admin styles look broken after enabling a plugin

**Cause.** A plugin shipped Tailwind preflight from its own bundle, which collides with Filament's. Or the plugin's `npm run build` produced a CSS file that targets `*` selectors.

**Fix.** Scope plugin Tailwind to a wrapper class. See [Assets and Vite](../guides/plugins/assets-vite.md).

### Plugin assets 404 after deploy

**Diagnose.**
- `public/plugins/{id}/` exists?
- The release archive’s `public/` allowlist includes `plugins/`, but it does **not** include arbitrary uploads — confirm your `dist/` was published before the archive was built.

**Fix.** Run `php artisan mks-plugin:publish {id}`, then re-build the archive (or manually upload `public/plugins/{id}/`).

## Migrations

- Plugin only: `php artisan mks-plugin:migrate {id}`. Runs only the migrations under `{plugin_root}/{id}/database/migrations/`.
- Full app: `php artisan migrate`.
- Rollback per-plugin is not supported. Roll back full migrations or write your own down-migration.

## Geo

| Symptom | Diagnose | Fix |
|---------|----------|-----|
| Checkout or admin address forms have no states/cities | `geo_states` / `geo_cities` empty for the enabled country | Run `php artisan mks:geo:import`. Cities need the MySQL `locations` database — see [Import and migration](../guides/geo/import-and-migration.md). |
| `mks:geo:import` fails on countries or states | Server has no outbound HTTP to GitHub raw (dr5hn) | Open firewall/proxy; or import countries/states manually then run `--only=cities`. |
| Cities phase skipped in import output | Log mentions locations DB unreachable | Provision MySQL `locations` with table `csv-cities`, or pass `--locations-database` / `--locations-table`. |
| Legacy address FKs still null after upgrade | `mks_ecom_iran_*` tables still exist | Run `mks:geo:import` first, then `php artisan mks:geo:migrate-legacy-iran`. |
| Country dropdown shows every country | `geo_enabled_countries` is empty in settings | Intentional (empty = all active countries). Restrict ISO2 codes under **System → Settings → Geo**. |

## See also

- [Validation checklist](validation-checklist.md) — verify a fresh install end to end.
- [Deployment and hosting](deployment-hosting.md) — production web server and release archive.
- [Release archive](release-archive.md) — what `mks:release-archive` does and doesn't do.
- [Commands reference](../reference/commands.md) — every Artisan command.
