---
title: Upgrade guide
description: Per-release migration notes for miran/mksine.
order: 1
---

# Upgrade guide

This file accumulates **migration notes** per release. Add a new section at the top whenever a release changes anything visible in [API stability](../reference/stability.md) (interfaces, commands, config keys, default behavior). Cross-link to `CHANGELOG.md`.

If a release contains only patch-level fixes with no migration steps, you do not need a section here.

## Template

When adding an entry, copy this skeleton:

```markdown
## X.Y.Z (YYYY-MM-DD)

### Breaking
- One-line summary. Migration: ...

### Deprecated
- One-line summary. Replacement: ... Removal target: vX+1.0.0.

### Behavior changes (non-breaking, but visible)
- One-line summary.
```

---

## 1.3.0 (2026-08-01)

### Behavior changes (non-breaking, but visible)

- **Form slot hooks.** Core resource forms expose named `before` / `after` / `replace` anchors. `FormHookManager::apply()` still runs whole-form callbacks first, then walks the schema for slots. Plugins that previously rewrote entire forms can migrate to slot helpers when they only need a precise injection point.
- **New form names:** `media.form`, `menu_location.form`, `geo_state.form` now call `FormHookManager::apply()`.
- **Section keys** on core forms are stable (`seo` → slot anchor `seo_section`). Media’s two `disk` fields use component keys `disk_create` / `disk_edit` for slots while keeping state path `disk`.

### Migration

1. Prefer `Hooks::afterFormComponent()` / `beforeFormComponent()` / `replaceFormComponent()` over whole-form rewrites when targeting a single field or section.
2. Re-run `php artisan mks:discover` if you add `FormSlotHookListenerInterface` classes.
3. Treat `replace` / hide as last-writer-wins when multiple plugins share an anchor.

---

## 1.2.0 (2026-07-22)

### Behavior changes (non-breaking, but visible)

- **Filament 5 / Livewire 4.** Package constraint is `filament/filament: ^4.0|^5.0`. On Livewire 4, MKSine registers components with `Livewire::addNamespace('mksine', …)` because `Livewire::component('mksine::…')` aliases are not resolved for `::` names. Page-builder components under `Core\PageBuilder\Livewire` use a missing-component resolver. Livewire 3 hosts keep the previous `Livewire::component()` registration path.
- **Published Livewire config stub** uses Livewire 4 keys (`component_layout`, `component_placeholder`). Existing hosts that already published `config/livewire.php` are unchanged until they re-publish or migrate keys manually when upgrading to Filament 5.

### Host migration to Filament 5

1. Ensure Laravel **11.28+** and Tailwind **4**.
2. Follow Filament’s [v5 upgrade guide](https://filamentphp.com/docs/5.x/upgrade-guide) (`filament/upgrade`, then `filament/filament:^5` + Livewire 4).
3. Update host `config/livewire.php`: rename `layout` → `component_layout`, `lazy_placeholder` → `component_placeholder` (keep MKSine `temporary_file_upload` limits).
4. Run your test suite; smoke-test MediaPicker, Page Builder, Menu Builder, and storefront routes.

---

## 1.1.0 (2026-07-08)

### Behavior changes (non-breaking, but visible)

- **Frontend admin bar (storefront).** WordPress-style toolbar for panel users. Menu items come from the runtime filter `frontend_admin_bar.items` (`FrontendAdminBar::HOOK_ITEMS`); plugins can add links and dropdowns via `FrontendAdminBarItem`. The panel brand label is not shown. Themes must include `@themeDoAction('layout.body_start')` in layouts. See [Frontend admin bar](../guides/storefront/frontend-admin-bar.md).
- **Shortcodes.** WordPress-style `[tag]` processing in rich text via `mks_render_content()`. Admin CKEditor fields include an **Insert shortcode** picker. Plugins register with `Hooks::addShortcode()` (optional `ShortcodeCatalogEntry` for the picker) or `Hooks::addLivewireShortcode()` for interactive widgets. Built-in: `[year]`, `[site_name]`. Render cache: `mksine.shortcodes.cache.*`. Feature flag: `mksine.features.shortcodes`. See [Shortcodes](../guides/content/shortcodes.md).
- **Admin "View site".** Filament topbar and user menu link to the active storefront (`ecom.shop` or `home`). Theme-independent.
- **Theme templates** — Default MKSine theme and page-builder blocks use `mks_render_content()`. Custom themes that still output raw `{!! $post->content !!}` will not process shortcodes until updated.

### New

- **`mksine.features.frontend_admin_bar`** (env `MKS_CMS_FRONTEND_ADMIN_BAR`, default `true`). Disables the storefront toolbar only.
- **`mksine.features.shortcodes`** (env `MKS_CMS_SHORTCODES`, default `true`), **`mksine.shortcodes.max_depth` / `max_passes`**, and **`mksine.shortcodes.cache.*`** (env `MKS_CMS_SHORTCODES_CACHE`, `_TTL`, `_STORE`). See [Shortcodes](../guides/content/shortcodes.md).
- Guide: [Frontend admin bar](../guides/storefront/frontend-admin-bar.md).
- Guide: [Shortcodes](../guides/content/shortcodes.md).

## 1.0.14 (2026-07-06)

### Behavior changes (non-breaking, but visible)

- **`mks:geo:import` runs on the queue by default.** The command prints a run ID and log path, then returns. Run `php artisan queue:work` on `config('mksine.geo_import.queue_connection')` / `queue_name` (defaults: app queue). For CI or one-shot local imports use **`--sync`**.
- **City import logs** — step-by-step progress in `storage/logs/mksine-geo-import/geo-import-{runId}.log`.

### New

- Config keys under **`mksine.geo_import`**: `queue_connection`, `queue_name`, `job_timeout`, `memory_limit`. See [configuration](../reference/configuration.md).

## Unreleased

### Breaking

- None yet.

### Deprecated

- `HookManager::enableListener()`, `HookManager::disableListener()`, `HookManager::setPriority()` — superseded by direct DB updates against `mks_hooks` (or the admin Hooks page). Removal target: `v2.0.0`.
- `MksineEvent::cancel()` is documented but not used by any first-party listener. Treat the cancellation state as advisory; the dispatcher does not abort once entered. Decision will be made by `v1.1.0` whether to enforce or remove.

### Behavior changes (non-breaking, but visible)

- Documentation tree restructured under `packages/mksine/docs/`. Old paths (`10-plugin-golden-path.md`, `40-security-auth.md`, etc.) have moved into topic directories; see `_nav.yml`. Internal links inside the package now use the new tree.
- Plugin source path is referenced as `{plugin_root}` (= `base_path(config('mksine.plugins_path'))`) throughout the docs. The default value is unchanged (`plugins`).
- `mksine.hooks.log_slow_hooks` and `mksine.hooks.slow_hook_threshold` are documented as **configured but not yet honoured** by `HookDispatcher`. No removal planned; implementation pending. See [Slow-hook logging](../guides/hooks/slow-hook-logging.md).
- `mksine.hooks.cache_discovery` is documented as **configured but not yet honoured** by `DiscoverHooksCommand`. Same status.
- `TableHookManager` `extend*` methods accept and return the full `Table` object (not arrays of components). Older docstrings implied otherwise; the implementation has always taken `Table`. Inline PHPDoc was updated for clarity.
- `TableHookManager::apply()` does **not** catch exceptions raised by registered callbacks (unlike `FormHookManager::apply()`, which logs and continues). This asymmetry is intentional but now explicitly documented.
- The `ComponentRegistry::validateComponent()` is invoked automatically when editors save block settings via **`PageBuilder::saveBlock`**. Imports, programmatic `builder_payload` writes, and integrations that bypass the modal must still recurse `validateComponent()` themselves. See [Validation](../guides/page-builder/validation.md).
- `MenuLocationManager::syncToDatabase()` only inserts new locations; it never updates `label` on existing rows or deletes removed locations. Document this whenever you change a location’s label in code.
- Page builder docs introduce the `{plugin_root}/{plugin_id}` convention for examples and stop referencing client-specific plugin IDs.

### New

- **Global geo system.** Core tables `geo_countries`, `geo_states`, `geo_cities`; **Settings → Geo**; Filament `GeoStateResource` + cities relation; HTTP **`/api/geo/*`**; commands **`mks:geo:import`** and **`mks:geo:migrate-legacy-iran`**. Ecom (and other plugins) consume via `StoreGeoSettings` / `GeoResolver`. See [Global geo system](../guides/geo/overview.md) and [Import and legacy migration](../guides/geo/import-and-migration.md). Setting keys moved from ecom to `geo_*` with legacy `ecom_*` fallback.
- **`mksine:create-super-admin`.** Creates a super admin user on the application database (role + `syncPermissions` for all existing permission rows). Documented in [commands](../reference/commands.md#mksinecreate-super-admin).
- **`mksine:install` publishes Shield / Spatie Permission assets** and, with `--migrate`, runs cache clears, `filament:assets`, `shield:generate --all` (when `MksinePlugin` is on the panel), and `mks:discover`. Register `MksinePlugin` on the Filament panel **before** `mksine:install --migrate` so permissions include CMS resources. See [Installation](../01-installation.md).
- **ZIP updater.** New first-class upgrade path for plugins, themes, and the core package via ZIP uploads. Available through the Filament UI (Manage Plugins, Theme Manager, System Update) and via CLI (`mks-plugin:update`, `mks:theme-update`, `mksine:update`, plus matching `*:rollback` commands). Designed for production servers without Composer or npm access — ZIPs must ship pre-built `dist/` assets and pre-vendored Composer dependencies. See [Operations → ZIP updater](../operations/zip-updater.md) and the new `updater.*` keys in [configuration](../reference/configuration.md#updater). No migration needed for existing installs; the feature is gated behind the Shield super-admin role and `config('mksine.updater.enabled')`.
- Documentation: full guide tree for plugins, hooks, themes, page builder, menus, media, settings, localization, auth.
- Documentation: deep dive on `mks:release-archive` covering build root discovery, the `public/` allowlist, and verification steps.
- Documentation: per-area troubleshooting and validation checklist sections expanded to cover menus, settings, translations, and media.
- Documentation: every page now carries YAML front matter (`title:` required) for SSG adapters (VitePress, Docusaurus, Starlight, Mintlify).
- Tooling: `php scripts/lint-docs.php` (and the matching `composer lint:docs` script and `tests/DocsNavTest.php` Pest suite) enforces that every Markdown page is in `_nav.yml` exactly once, that every nav entry exists on disk, and that every page has a non-empty `title:` in its front matter. Wired into a `.github/workflows/docs-lint.yml` workflow that runs on every PR touching `docs/`.

## 1.0.0 — initial release placeholder

The current `CHANGELOG.md` lists `1.0.0 — 202X-XX-XX` as a placeholder. When 1.0.0 actually ships:

- Move every Unreleased entry above into a `## 1.0.0 (date)` section.
- Confirm [API stability](../reference/stability.md) reflects the surface that ships.
- Link from the corresponding `CHANGELOG.md` line back to this guide.
