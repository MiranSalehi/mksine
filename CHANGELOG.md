# Changelog

All notable changes to `mksine` will be documented in this file.

See [`docs/meta/upgrade-guide.md`](docs/meta/upgrade-guide.md) for migration notes per release and [`docs/meta/versioning.md`](docs/meta/versioning.md) for the semver policy.

## Unreleased

- (none)

## 1.0.11 - 2026-06-30

### Fixed

- **`mksine:install` publishes storefront theme assets** — runs `mks:theme-publish` so package themes (e.g. `mksine`) copy `dist/` and `images/` to `public/vendor/mksine/themes/{id}/`. Fixes unstyled homepage with 404s on `app.css`, `custom.css`, and theme images after Composer install.

## 1.0.10 - 2026-06-30

### Fixed

- **Plugin `sync:mksine-tailwind` on Composer vendor installs** — skip rebuilding admin CSS under `vendor/miran/mksine` (Filament import paths differ from the monorepo); use shipped `resources/dist/mksine.css` instead. Full rebuild remains for `packages/mksine` development only.

## 1.0.9 - 2026-06-30

### Fixed

- **Plugin `sync:mksine-tailwind` on Composer installs** — `bin/build-styles.js` no longer fails when `package.json` is missing from the vendor tree; skips gracefully when pre-built `resources/dist/mksine.css` is present. `package.json` is now included in Composer dist archives for optional `npm install` + full rebuilds.

## 1.0.8 - 2026-06-30

### Fixed

- **Plugin `npm run build` on Composer installs** — `sync:mksine-tailwind` and `sync:filament-assets` now resolve `vendor/miran/mksine` instead of the monorepo-only `packages/mksine` path. Added `bin/build-styles.js` for cross-platform Windows support.

## 1.0.7 - 2026-06-30

### Fixed

- **Plugin ZIP upload** — Apply Livewire upload limits earlier (`packageRegistered`), default temp disk to `local`, use `mimes:zip` instead of strict `mimetypes` (Windows-friendly), and rediscover plugins after a successful upload.
- **Discover Plugins** — Admin button now clears discovery cache and rescans `config('mksine.plugins_path')` instead of only showing a notification.
- **Plugin discovery paths** — `PluginDiscovery` honours `mksine.plugins_path` and scans any non-vendor plugin root directory (not only a folder literally named `plugins`).

## Unreleased (docs backlog)

- **Global geo system.** Core tables `geo_countries`, `geo_states`, `geo_cities`; **System → Settings → Geo**; Filament `GeoStateResource` + cities relation; HTTP **`/api/geo/*`**; commands **`mks:geo:import`** and **`mks:geo:migrate-legacy-iran`**. Plugins consume via `StoreGeoSettings` / `GeoResolver`. Setting keys moved from ecom to `geo_*` with legacy `ecom_*` fallback. See `docs/guides/geo/`.
- Restructure developer documentation under `packages/mksine/docs/` into the final tree: `00-introduction.md`, `01-installation.md`, `02-quickstart.md`, plus `concepts/`, `guides/`, `reference/`, `operations/`, and `meta/` directories. Add `_nav.yml` as the SSG-agnostic sidebar source.
- Introduce `docs/reference/stability.md` to define the public API surface (interfaces, facades, managers, commands, configuration) covered by semver.
- Move and expand prior single-page docs:
  - `docs/40-security-auth.md` → `docs/guides/auth/user-subclass.md`
  - `docs/50-troubleshooting.md` → `docs/operations/troubleshooting.md`
  - `docs/60-deployment-hosting.md` → `docs/operations/deployment-hosting.md`
  - `docs/99-validation-checklist.md` → `docs/operations/validation-checklist.md`
  - `docs/DEVELOPER-SLO.md` → `docs/meta/slo.md`
- Document plugin paths via `config('mksine.plugins_path')` / `{plugin_root}` instead of a monorepo-specific `plugins/` tree.
- Update `README.md` and `tests/Unit/MksinePackageDocsTest.php` to mirror the new tree.

## 1.0.0 - 202X-XX-XX

- initial release
