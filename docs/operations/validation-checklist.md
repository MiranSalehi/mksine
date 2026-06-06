---
title: Validation checklist
description: Run through this after install, after upgrade, or when onboarding a new environment.
order: 3
---

# Validation checklist

Use this after [Installation](../01-installation.md), after an upgrade, or when onboarding a new environment.

## Environment

- [ ] PHP version matches the package constraint (`^8.2`).
- [ ] Laravel and Filament versions match the host app’s declared constraints.
- [ ] Database reachable; `php artisan migrate` succeeds for the core app.
- [ ] `php artisan mksine:info` runs without errors (lists package version and active feature flags).

## Production / web server

- [ ] HTTP document root is Laravel **`public/`** (contains `index.php` and `.htaccess`).
- [ ] **Apache:** `mod_rewrite` and `AllowOverride` allow `public/.htaccess` rules (or equivalent virtual host config).
- [ ] **nginx:** `try_files` (or equivalent) sends non-file requests to `index.php`; `root` points at `public/`.
- [ ] Smoke test: `GET /livewire/livewire.js` returns **200** when `APP_DEBUG=true`, or `GET /livewire/livewire.min.js` when `APP_DEBUG=false` (after `config:cache` if used).
- [ ] After changing `.env`, `php artisan config:clear` or `php artisan optimize:clear` was run so cached config does not serve stale `debug` or `URL` settings.

See [Deployment and hosting](deployment-hosting.md).

## Plugin lifecycle

- [ ] `php artisan mks-plugin:discover` lists the new plugin (or the cache file is updated).
- [ ] `php artisan mks-plugin:install {id}` completes without exception.
- [ ] `php artisan mks-plugin:activate {id}` completes; `mks_plugins` shows active; no boot error.
- [ ] `php artisan mks-plugin:migrate {id}` succeeds if the plugin ships migrations.
- [ ] Boot guard not engaged (`mks_plugins.boot_error` is null).

## Admin UI

- [ ] Log in as a user with permission to open the new resource.
- [ ] New resource appears in navigation (or its direct URL works).
- [ ] Create + list + edit smoke test passes for at least one resource per plugin.

## Assets and lang (if applicable)

- [ ] `npm run build` in the plugin succeeds locally.
- [ ] `php artisan mks-plugin:publish {id}` ran; `public/plugins/{id}/` contains expected files.
- [ ] `php artisan mks-plugin:publish-lang {id}` ran if translations are used; `lang/vendor/{id}/` is present.
- [ ] Planned git commit includes published artifacts if production has no Node.

## Hooks

- [ ] Listener classes live under a path covered by `mks:discover` (the package’s own paths or `mksine.hooks.discovery_paths`).
- [ ] `php artisan mks:discover` ran after changes; expected rows appear in `mks_hooks` (or the listener is documented as runtime-only).
- [ ] If you rely on slow-hook detection, you have wired the per-listener timing pattern from [Slow-hook logging](../guides/hooks/slow-hook-logging.md). The kernel does not currently honour `mksine.hooks.log_slow_hooks` natively.

## Async hooks (if applicable)

- [ ] `php artisan queue:work` runs on the configured `mksine.hooks.queue.connection` and `queue`.
- [ ] Failed-job table contains no entries from queueable hook events after a smoke run.

## Themes (if `features.theme_management` is on)

- [ ] `php artisan mks:theme-publish {theme}` succeeded; theme assets live under `public/themes/{theme}/`.
- [ ] Theme appears in the picker and switching it does not produce view-not-found errors.

## Page builder (if `features.page_builder` is on)

- [ ] At least one block is registered (verify in the page-builder editor).
- [ ] Saving and rendering a page with that block produces the expected HTML.

## Menus (if used)

- [ ] All theme-required locations are registered (`MenuLocationManager::registerLocations`).
- [ ] `MenuLocation::syncToDatabase()` ran (open the admin Menu Locations page once, or call from a seeder).
- [ ] Each location used by the theme has a menu assigned (`menu_location_assignments`).
- [ ] Custom item sources are registered in `boot()` and appear as tabs in the Menu Builder.

## Settings

- [ ] Settings page loads; core tabs (`general`, `permalinks`) render without errors.
- [ ] Plugin-supplied tabs appear with the correct `sortOrder`.
- [ ] Saving a value and re-reading via `mks_setting('key')` returns the expected type (string vs JSON-decoded array).

## Geo (if checkout/addresses use geo)

- [ ] `php artisan migrate` created `geo_countries`, `geo_states`, `geo_cities`.
- [ ] `php artisan mks:geo:import` (or `mks:geo:import --country=IR` for staging) completed without errors.
- [ ] **System → Settings → Geo**: enabled countries and default country configured.
- [ ] `geo_countries` has at least one row; states and cities exist for the active country.
- [ ] If legacy Iran tables remain: `php artisan mks:geo:migrate-legacy-iran` ran once after import.
- [ ] `GET /api/geo/countries` returns JSON with a `data` array.
- [ ] (ecom) Checkout and admin address forms persist `geo_country_id` / `geo_state_id` / `geo_city_id` — see `plugins/ecom/docs/guides/addresses-and-geo.md`.

## Translations

- [ ] `lang/{locale}/` exists for every locale you intend to support.
- [ ] Plugin/theme translation source paths are reachable (`AdminTranslationManager` lists them in the source dropdown).
- [ ] `lang/vendor/{plugin_id}/` and `lang/vendor/theme-{id}/` are populated when needed (admin save copies on demand; or run the publish commands explicitly).

## Media library

- [ ] `mksine.media.disk` exists in `config/filesystems.php` and is writable by the web user.
- [ ] `php artisan storage:link` ran (or you’re using a non-symlink configuration intentionally).
- [ ] Upload smoke test: a small image uploads and displays in the library.
- [ ] Thumbnails generate (if `generate_thumbnails = true`) — `getimagesize` succeeds and required image libraries are installed.

## Security

- [ ] `php artisan mksine:install --migrate` (or equivalent publish) created permission tables; `shield:generate --all` ran; permissions exist for every package and plugin resource.
- [ ] At least one super admin exists (`mksine:create-super-admin` or `shield:super-admin`).
- [ ] No unauthorized access to new resources (test as a non-admin user).
- [ ] If a user subclass is in use: auth + `mksine.user_model` + Shield provider model are aligned. See [User subclass](../guides/auth/user-subclass.md) and [Shield and policies](../guides/auth/shield-and-policies.md).
- [ ] `mksine.security.authorize_media` is set per your privacy needs (default: false → public bucket).
- [ ] No secrets in the release archive (`unzip -l` and grep for `.env`, API keys, private certs).

## Release archive (before deploying via zip)

- [ ] `php artisan mks:release-archive --dry-run` shows the expected build roots in the expected order.
- [ ] `npm run build` succeeded in every listed root (the command fails fast if not).
- [ ] `unzip -l` confirms `vendor/miran/mksine/` paths exist (path repository symlink preserved).
- [ ] `public/build/`, `public/plugins/{id}/`, `public/themes/{id}/` are present in the zip.
- [ ] `.env` is **not** present; `.env.example` is.
- [ ] Cleared caches (`php artisan optimize:clear`) before building so `bootstrap/cache/*` doesn’t bake in dev paths.

See [Release archive](release-archive.md).
