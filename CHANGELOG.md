# Changelog

All notable changes to `mksine` will be documented in this file.

See [`docs/meta/upgrade-guide.md`](docs/meta/upgrade-guide.md) for migration notes per release and [`docs/meta/versioning.md`](docs/meta/versioning.md) for the semver policy.

## Unreleased

- (none)

## 1.5.1 - 2026-08-26

### Fixed

- **SQLite fresh install** — `morph_commentable_on_comments_table` now drops the `comments_post_id_status_index` index before removing `post_id`, fixing `General error: 1 error in index comments_post_id_status_index after drop column: no such column: post_id` on `DB_CONNECTION=sqlite` (MySQL/MariaDB installs were unaffected).

## 1.5.0 - 2026-08-26

### Added

- **Theme plugin dependencies** — `theme.json` supports `requires.plugins`; `ThemeDependencyChecker` resolves missing plugins; `EnsureActiveThemeDependencies` middleware shows a storefront warning page (HTTP 503) instead of runtime view errors when required plugins are inactive.
- **Admin dependency warnings** — Theme Manager badges and activation notices, a Filament admin banner, and plugin deactivation warnings when the active theme declares inactive plugin dependencies.
- **Default theme placeholder homepage** — the bundled `mksine` theme shows a skeleton placeholder index on empty sites until a Front Page is configured in Settings → Permalinks (marketing blocks remain available via the page builder template only).

### Changed

- **Default `mksine` theme home** — `home.blade.php` includes only the placeholder partial instead of the full marketing landing on an empty database.
- **Default theme header** — removed the manual EN/FA/KU locale toggle; `lang` and `dir` follow `config('app.locale')` (RTL for `fa`, `ar`, `ku`, `he`).
- **Installation docs** — document removing Laravel’s default `/` route from `routes/web.php`; troubleshooting and validation checklist updated.

## 1.4.0 - 2026-08-10

### Added

- **WordPress-style admin sidebar** — labeled navigation groups with 2+ children open a hover flyout beside the parent (desktop); click opens the first child. Solo groups (1 child) render as a top-level parent without a chevron.
- **`AdminNavigationGroup` enum** — locale-stable group identity (`HasLabel` + `HasIcon`) so group icons survive UI locale changes.
- **Tools** navigation group label translations (`en` / `fa` / `ku`).

### Changed

- Core Filament resources/pages prefer returning `AdminNavigationGroup` (via `AdminSidebarNavigation::case()`) from `getNavigationGroup()` instead of translated strings.
- `AdminSidebarNavigation::panelGroups()` registers groups with Closure labels resolved at render time.
- Media custom navigation items use Closure labels so child flyout labels follow the active locale.
- Ungrouped leaves (Dashboard, Plugins, Settings) stay visible when the sidebar is open; only labeled parent/solo groups use the flyout hide rules.

### Fixed

- Mixed Persian/English sidebar labels when Language Switch locale differed from bootstrap registration time.
- Flyout vs Filament accordion dual display and hover flicker (dedicated flyout panel; children stay in place).
- Parent chevron direction: physical CSS chevrons point toward the flyout (`>` LTR / `<` RTL) without Unicode bidi mirroring.
- Flyout child icons cloned from the collapsed-sidebar dropdown when open-sidebar strips item icons.

### Notes

- Plugins that still return translated group *strings* may lose icons when the UI locale changes — migrate to `AdminNavigationGroup` / `AdminSidebarNavigation::case()`.
- Rebuild/publish admin assets after upgrade (`npm run build:styles` in the package when developing from source, then `php artisan filament:assets`).

## 1.3.0 - 2026-08-01

### Added

- **Named form slot hooks** on core Filament resource forms: `{form}.before.{anchor}`, `{form}.after.{anchor}`, `{form}.replace.{anchor}` (replace returning `null` / `[]` hides the component).
- `FormHookManager::extendSlot()`, `FormSlotApplicator`, and `Hooks::beforeFormComponent()` / `afterFormComponent()` / `replaceFormComponent()`.
- Discoverable `FormSlotHookListenerInterface` (`hook_type = form_slot` via `mks:discover`).
- Whole-form hook wiring for `media.form`, `menu_location.form`, and `geo_state.form`.
- Stable section `->key()` anchors and Media `disk_create` / `disk_edit` component keys for slot targeting.

### Notes

- Whole-form `extend()` callbacks still run first; slot hooks apply afterward while walking the schema tree.
- Replace/hide is last-writer-wins across plugins — coordinate carefully.
- See [Form hooks](docs/guides/hooks/form-hooks.md) for the per-resource anchor tables.

## 1.2.0 - 2026-07-22

### Added

- **Filament 5 support** — `filament/filament` constraint is now `^4.0|^5.0`. Filament 5 hosts resolve Livewire 4 automatically.
- Livewire 4 registration via `Livewire::addNamespace('mksine', …)` with a missing-component fallback for page-builder classes under `Core\PageBuilder\Livewire`.

### Changed

- Published `config/livewire.php` stub aligned with Livewire 4 (`component_layout`, `component_placeholder`).
- Livewire event listeners on MediaPicker / PageBuilder / ComponentEditor use `#[On]` instead of `$listeners`.
- Theme comment scaffolds and default theme use `wire:model.live` for Livewire 4-friendly binding.
- Comment star rating uses explicit `setRating()` instead of `wire:click="$set(...)"` (broken under Livewire 4).
- `MenuBuilder` no longer implements leftover `HasForms` (Action schemas only).

### Notes

- Filament 5 requires Livewire 4, Laravel 11.28+, and Tailwind CSS 4 on the host. Filament 4 + Livewire 3 installs continue to work via the dual Composer constraint.
- See [Upgrade guide](docs/meta/upgrade-guide.md) for host migration steps.

## 1.1.1 - 2026-07-09

### Fixed

- **MediaPicker pagination** — Compact Livewire pagination with `onEachSide(1)` (e.g. `‹ 1 2 … 56 ›` instead of listing every page). Modal-themed styling, dark mode, and RTL chevrons. Replaces the default Livewire Tailwind view that duplicated Previous/Next controls.

### Changed

- **README** — Expanded install steps, feature highlights, and Filament marketplace doc link.
- **Filament listing assets** — Demo GIFs/PNGs under `docs/assets/filament/` for marketplace documentation.

## 1.1.0 - 2026-07-08

### Added

- **Frontend admin bar** — WordPress-style storefront toolbar for users who can access Filament. Menu items via filter `frontend_admin_bar.items` and `FrontendAdminBarItem` (links and dropdowns). Feature flag: `mksine.features.frontend_admin_bar` (env `MKS_CMS_FRONTEND_ADMIN_BAR`). Themes must call `@themeDoAction('layout.body_start')` in layouts.
- **Shortcodes** — WordPress-style `[tag]` processing in rich text via `mks_render_content()`. Built-in tags: `[year]`, `[site_name]`. Plugins register with `Hooks::addShortcode()` (optional `ShortcodeCatalogEntry` for the admin picker) or `Hooks::addLivewireShortcode()` for interactive Livewire widgets. Helpers: `mks_strip_shortcodes()`, `mks_shortcode_context()`. Filters: `mksine.content.before_shortcodes`, `mksine.content.after_shortcodes`, `mksine.shortcode.{tag}`, `mksine.shortcodes.admin_catalog`.
- **CKEditor Insert shortcode** — Toolbar button opens a catalog modal populated from `ShortcodeRegistry::adminCatalog()`.
- **Shortcode render cache** — Config keys `mksine.shortcodes.cache.enabled`, `ttl`, `store` (env `MKS_CMS_SHORTCODES_CACHE`, `_TTL`, `_STORE`). Skipped for Livewire shortcodes and during unit tests.
- **Admin View site** — Filament topbar and user menu link to the active storefront URL (`StorefrontUrl`; `ecom.shop` when registered, otherwise `home`).
- Documentation: [Shortcodes](docs/guides/content/shortcodes.md), [Frontend admin bar](docs/guides/storefront/frontend-admin-bar.md).

### Changed

- Default MKSine theme templates (`single`, `page`) and page-builder render views (text, tabs, accordion) call `mks_render_content()` instead of raw HTML output.
- `ThemeMakeCommand` scaffolds include `@themeDoAction('layout.body_start')` for admin bar compatibility.

## 1.0.14 - 2026-07-06

### Added

- **Geo import queue jobs** — `RunGeoImportJob` orchestrates countries/states and batches `ImportGeoCitiesCountryJob` per country; progress logged to `storage/logs/mksine-geo-import/geo-import-{runId}.log` and Laravel log.
- **`mks:geo:import --sync`** — run import synchronously (previous default behaviour).
- **`mksine.geo_import.*` config** — `queue_connection`, `queue_name`, `job_timeout`, `memory_limit`.

### Changed

- **`mks:geo:import`** dispatches to the queue by default; cities are imported one country per job to limit memory use.

### Fixed

- **Cities import memory exhaustion** — translations and city rows are processed per country instead of loading all mapped countries at once.

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
