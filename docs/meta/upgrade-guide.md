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

## Unreleased

- (none)

## 1.12.0 (2026-10-06)

### Breaking

- **Plugin and theme management is Super Admin only.** Uploading, installing, activating, deactivating, uninstalling and deleting plugins, activating and deleting themes, and editing a theme's Custom CSS/JS now require the Shield super admin role (`config('filament-shield.super_admin.name')`). Page-level Shield permissions are no longer sufficient, because every one of these actions puts executable code on disk or into the page. Migration: assign the super admin role to whoever performs these operations, or perform them from the CLI (`php artisan mks:plugin:*`).

- **Uploaded archives must carry a safe identifier.** A plugin's manifest `id` must match `/^[a-z0-9][a-z0-9_-]{0,63}$/`, and a theme's folder name or `theme.json` `name` must reduce to the same shape. Archives whose identifier cannot be validated are rejected instead of being written to a computed path. Migration: rename the plugin `id` / theme folder before packaging. Already-installed packages are unaffected — validation only runs on upload.

- **Plugin manifests are read without being executed.** `plugin.php` is parsed by a tokeniser during upload, so only literal string values for `id`, `name` and `version` in the outermost `return [...]` array are recognised. A manifest that computes its `id` (concatenation, constants, function calls) is rejected as missing an id. Migration: use a plain string literal for `id`.

- **Theme custom asset helpers validate their identifier.** `ThemeManager::getCustomStoragePath()`, `getCustomContent()`, `hasCustomAsset()`, `getExtraAssets()` and `getExtraAssetsStoragePath()` throw `InvalidArgumentException` for identifiers containing a path separator, a leading dot, or a NUL byte. Callers passing a discovered theme's identifier are unaffected.

- **The admin terminal only runs allowlisted sub-commands.** `mksine.console_terminal.allowed_commands` gates both runners; anything outside it is rejected with a message naming the config key. The default list covers cache/optimize, `migrate`, `migrate:smart`, `queue:*`, `schedule:*`, `filament:*`, `shield:*`, `mks:*`, `mks-plugin:*`, `mksine:update`, `mksine:create-super-admin`, and the common Composer sub-commands. Migration: publish the config and extend the list, or set `'artisan' => ['*']` to restore the previous unrestricted behaviour.

- **The media picker requires media permissions.** Users without `viewAny` on `Miran\Mksine\Models\Media` see an empty picker and cannot open it; uploading additionally requires `create`. Super Admins always pass. Installs with no registered `Media` policy keep the previous behaviour, since there is no permission model to consult. Migration: grant `ViewAny:Media` / `Create:Media` to roles that attach images.

- **Protected roles are hidden from the user form.** Users who are not Super Admins no longer see the super admin role in the roles checkbox list, and cannot edit, delete, restore or force-delete an account that holds it. Migration: none, unless a non-super-admin role was relied upon to manage Super Admin accounts.

- **SVG uploads are off by default and validated when on.** `image/svg+xml` has been removed from `mksine.media.allowed_types`, so SVG uploads are rejected as a disallowed type. If you re-add it, uploads still have to clear `Miran\Mksine\Support\SvgSafety`, which rejects a document containing `<script>`, any `on*` attribute, `<foreignObject>`, `<animate>` / `<set>`, an `<iframe>` / `<embed>` / `<object>`, a `javascript:` / `data:text/html` URI, a `<use>` pointing anywhere but a same-document fragment, an `@import` in CSS, or an internal DTD subset — and rejects anything over 2 MB or not well-formed XML. The check is keyed off the filename extension as well as the detected mime. Migration: if your site relies on SVG logos or icons, add `'image/svg+xml'` back to the published config and re-test those assets; plain exported icons pass, but an SVG with embedded interactivity does not.

- **Livewire no longer previews SVG uploads.** `svg` is out of `livewire.temporary_file_upload.preview_mimes`. The preview route serves the temporary file on this origin with its own content type *before* validation runs, so leaving it in meant an SVG was executable at upload time regardless of the media allowlist. Migration: if you published `config/livewire.php`, remove `'svg'` from `preview_mimes` there too.

- **Theme and plugin screenshots are refused if they are scriptable SVGs.** Both routes now 404 an SVG that fails the check, and send `X-Content-Type-Options: nosniff` plus a `default-src 'none'; … sandbox` CSP on every screenshot. Migration: ship a PNG screenshot, or strip scripting from the SVG.

- **Media file metadata is read from disk, not from the edit form.** `file_name`, `mime_type`, `size`, `width`, `height`, `path` and `url` are display-only (`dehydrated(false)`). Saving a media record re-detects those attributes from the stored file. Migration: none for legitimate edits. If you had been patching `mime_type` by posting the disabled field, that write is ignored; fix the file instead. Saving a record whose mime was previously spoofed repairs it from the bytes on disk.

- **Public comments require a registered commentable type.** `PostComments` no longer accepts every Eloquent model. The class must be listed in `mksine.commentable_types` and implement `Miran\Mksine\Contracts\AllowsPublicComments`, and `allowsPublicComments()` must return true. Guest submissions are limited to `mksine.comments.max_per_minute` (default 5) per IP per `mksine.comments.decay_seconds` (default 60). Migration: core `Post` is already listed. Plugins that render `@livewire('mksine::frontend.post-comments', ['commentableType' => SomeModel::class, ...])` must merge `SomeModel::class` into the config (as ecom already does for Product) and implement the interface. Types that only exist as Eloquent models are rejected.

- **Marketplace listings must be signed.** `archive_sha256` is no longer treated as proof of origin. Each catalog row needs `archive_signature` — Ed25519 over this canonical message (`kind` is `plugin` or `theme`):

  ```
  mksine-marketplace-v1
  {kind}
  {package_id}
  {version}
  {sha256}
  ```

  The matching public key ships in the package; extra keys go in `mksine.marketplace.signing_public_keys` / `MKS_MARKETPLACE_SIGNING_PUBLIC_KEYS`. Catalog and download HTTP do not follow redirects. A signature that is present is always verified. `require_release_signature` defaults to false because the public catalog does not send `archive_signature` yet; requiring it hides every listing. Turn `MKS_MARKETPLACE_REQUIRE_RELEASE_SIGNATURE=true` on after the API signs (`php artisan mksine:sign-marketplace-release plugin {package_id} {version} {sha256} --secret=/offline/key.sec`).   Do not add `mksine:sign-marketplace-release` to the admin terminal allowlist, and never commit a `.sec` file.

### Behavior changes (non-breaking, but visible)

- **Plugins can register a Filament panel from `plugin.php`.** Optional `filament_panel_provider` is registered during package `register()` for every discovered plugin, before Filament builds panel routes. Deactivating a plugin does not remove that panel. Panel access for a plugin-owned panel is the `mksine.user.can_access_panel` filter on `InteractsWithMksine::canAccessPanel()`; a listener must return a bool only for its own panel.
- **`mks:discover` scans `{plugin}/src/Hooks/Listeners`.** A missing directory is skipped with no warning. `hooks.discovery_paths` is unchanged. Re-run `php artisan mks:discover` after upgrade so existing plugin listeners are synced.
- **Marketplace catalog cards.** Three plugin cards per row on large screens. Screenshots keep their own aspect ratio. HTTP browse timeouts default to 15s / 5s.

### Known limitation

- Installing a plugin still means running its code: plugin discovery `require`s `plugin.php` for every directory under `plugins/`. What changed is that upload validation no longer executes anything, and only Super Admins can put an archive there in the first place.

- SVG hardening only covers new uploads and the screenshot routes. Files already on the public disk are served directly by the web server, which Laravel cannot put headers on — see migration step 2. The durable fix is serving user uploads from a separate origin.

- The ecom plugin's JSON product-comment endpoint is a separate public writer and is not covered by this Livewire rate limiter.

- Marketplace signatures authenticate the ZIP identity (kind, package_id, version, hash). They do not review the code inside the archive. A valid signature means MKSine published that exact file, not that the file is safe.

### Migration

1. Confirm the accounts that manage plugins and themes hold the super admin role: `php artisan tinker --execute 'App\Models\User::role(config("filament-shield.super_admin.name"))->pluck("email");'`
2. **Audit the SVGs you already have.** Nothing in this release touches files uploaded before the upgrade, and they are still served from the site's own origin:

   ```bash
   php artisan tinker --execute '
   Miran\Mksine\Models\Media::where("mime_type", "like", "%svg%")
       ->orWhere("file_name", "like", "%.svg")
       ->get()
       ->each(function ($media) {
           $path = Illuminate\Support\Facades\Storage::disk($media->disk)->path($media->path);
           $status = Miran\Mksine\Support\SvgSafety::fileIsSafe($path) ? "ok    " : "UNSAFE";
           echo "{$status} #{$media->id} {$media->path}\n";
       });'
   ```

   Treat every `UNSAFE` line as a live XSS payload, not a formatting warning: delete or replace the file.
3. If you published `config/mksine.php`, merge `marketplace.require_release_signature` and `marketplace.signing_public_keys`. See [Configuration](../reference/configuration.md).
4. After upgrade: `php artisan optimize:clear`. If Add from MKSine is empty or shows a signature error, the catalog is not signing yet — either start signing on mksine.com or set `MKS_MARKETPLACE_REQUIRE_RELEASE_SIGNATURE=false` until it does.

## 1.11.1 (2026-09-20)

### Behavior changes (non-breaking, but visible)

- **Admin CSS load path.** 1.11.0 pointed the panel at `/mksine/admin-styles.css`. Hosts that only proxy `/admin` and `/css` never received the stylesheet, so catalog Heroicons rendered at the browser default (~300×150). 1.11.1 prefers `/css/miran/mksine/mksine-styles.css` and copies the package dist there when stale.

### Migration

1. After upgrade: `php artisan optimize:clear`. Open Plugins once so the published CSS can sync.
2. `filament:assets` is not required if `public/css` is writable.

## 1.11.0 (2026-09-20)

### Behavior changes (non-breaking, but visible)

- **Marketplace plugin cards.** Add from MKSine uses 16:10 covers like the public directory, plus title, summary, author, stats, and category · version · license. Installed / available / update stay visually distinct without washing the cover.
- **Admin CSS.** Panel styles are served from the package at `/mksine/admin-styles.css?v={filemtime}`. Catalog HTTP cache keys include `mksine.version`.

### Migration

1. After upgrade: `php artisan optimize:clear`.
2. If Plugins / Themes icons are huge, upgrade to 1.11.1 — this release’s CSS URL is often blocked on production.

## 1.10.0 (2026-09-20)

### Behavior changes (non-breaking, but visible)

- **Marketplace catalog UI.** Add from MKSine plugin listings use directory-style cards. Catalog JSON `downloads`, `rating_average`, and `rating_count` are shown when present; empty stats stay hidden. Installed vs available vs update-ready cards are visually distinct.
- **Catalog fetch.** Browse HTTP defaults are `marketplace.timeout` 6s and `connect_timeout` 2s; retries only on connection errors. The index response is cached (`cache_seconds` / `cache_stale_seconds`) and kept when switching back to the tab.

### Migration

1. If you published `config/mksine.php`, merge `marketplace.timeout` / `connect_timeout` / `cache_seconds` / `cache_stale_seconds` if those keys are missing. See [Configuration](../reference/configuration.md).
2. After upgrade: `php artisan optimize:clear`.

## 1.9.0 (2026-09-18)

### Added

- **Marketplace install.** Plugins and Theme Manager fetch `GET {marketplace.api_url}/plugins` and `/themes`, then download the listing ZIP onto this site. Super-admin can update a project plugin/theme when the catalog version is newer.
- **Plugin screenshots.** Optional `screenshot` in `plugin.php`; served at `/mksine/plugin/{id}/screenshot`.
- **Content import.** `Miran\Mksine\Core\Content\ContentImport` plus filter `mksine.import.row`.
- **Hooks.** `SystemEventCatalog`; `post.published`; `mksine.content.visible` / `mksine.content.query`; `mksine.storefront.viewed`; `mksine.storefront.not_found`; `mksine.content.slug_changed`.
- **Plugin install migrations.** Plugin migration files run through the migrator even when the host app removed Laravel’s `migrate` command.

### Migration

1. If you published `config/mksine.php`, merge `marketplace.api_url`, `timeout`, `connect_timeout`, `download_timeout`, `cache_seconds`, and `cache_stale_seconds`. See [Configuration](../reference/configuration.md).
2. After upgrade: `php artisan optimize:clear`.

## 1.8.0 (2026-09-15)

### Added

- **Gallery reorder.** `media_attachments.sort_order` (unsigned int, default `0`, backfilled from `id` so previous `orderBy('id')` order is kept). `MediaPicker::multiple()` is reorderable by default; `->reorderable(false)` to opt out. Read with `$model->getMediaCollection($collection)` (now ordered) or `getOrderedMedia()`. See [Media library](../guides/media/library.md).
- **SEO analysis.** Advisory scorer and Filament panel on Post, Page, Category, and Tag. Nullable `focus_keyphrase` (max 191) on those tables. Embed `SeoAnalysis::make()` on other resources; append checks with `Hooks::addFilter(SeoAnalyzer::FILTER_CHECKS, …)` (`mksine.seo.analysis_checks`). English may show a Flesch-like number; fa/ku use heading/sentence/paragraph heuristics only. See [SEO analysis](../guides/seo/analysis.md).

### Migration

1. Run `php artisan migrate` (sort_order + focus_keyphrase). `optimize:clear` is not required unless config is cached.

## 1.7.0 (2026-09-15)

### Behavior changes (non-breaking, but visible)

- **MediaPicker A/V.** Fields that pass `acceptedFileTypes(['video/*'])` or `['audio/*']` can browse, upload, and select those types. Fields that omit `acceptedFileTypes` still default to `image/*`.
- **Page builder.** New Video and Audio blocks in the media category.
- **CKEditor.** Insert media can add video and audio HTML, not only images.
- **Marketplace tab.** Plugins and Theme Manager show **Add from MKSine** as a coming-soon catalog that links to [mksine.com/marketplace](https://mksine.com/marketplace). In-panel install from the directory is not wired yet.

### Migration

1. If you published `config/mksine.php`, merge `marketplace.url` / `marketplace.directory_url` and the expanded `media.allowed_types` list (ogg video plus common audio mimes). See [Configuration](../reference/configuration.md).
2. Image-only pickers need no change. To allow A/V on a field: `->acceptedFileTypes(['video/*'])` or `['audio/*']`.
3. After upgrade: `php artisan optimize:clear`.

---

## 1.6.0 (2026-09-09)

### Added

- **Native tags.** Flat, polymorphic tags on `Post` and `Page` (`tags` + `taggables`). Admin: Content → Tags, plus a tags select on post and page forms. Storefront: `/tags` and `/tag/{slug}` (permalinks `tags_url` / `single_tag_url`). Archives list published posts and published pages in separate sections.

### Migration

1. Run `php artisan migrate` (creates `tags` and `taggables`).
2. Run `php artisan shield:generate --all` so Filament Shield picks up `ViewAny:Tag` and related permissions.
3. **Spatie `laravel-tags` clash.** Core uses tables `tags` and `taggables` and a public `tags()` relation on `Post`/`Page`. If a plugin already used Spatie `laravel-tags` on those models, rename or drop Spatie’s tables/relation before upgrading — they are not compatible.
4. Custom themes: add `tag.blade.php` and `tags.blade.php`. The active host theme does **not** fall back to the bundled `mksine` theme views.
5. New permalink settings: Tags URL (`/tags`) and Single Tag URL (`/tag/{slug}`).

---

## 1.5.1 (2026-08-26)

### Fixed

- **SQLite `mksine:install --migrate`.** If you use `DB_CONNECTION=sqlite` and migrations failed on `2026_04_28_180000_morph_commentable_on_comments_table`, update to this release and run `php artisan migrate` again on a clean database (or roll back the failed batch first). MySQL/MariaDB hosts need no action.

---

## 1.5.0 (2026-08-26)

### Behavior changes (non-breaking, but visible)

- **Theme plugin dependencies.** Project themes may declare required plugins in `theme.json` (`requires.plugins`). When the active theme’s dependencies are not satisfied, the storefront shows a warning page instead of throwing view errors; the admin panel shows banners and Theme Manager notices.
- **Default homepage.** With no Front Page configured, the bundled `mksine` theme renders a placeholder index instead of the marketing demo blocks. Assign a page under Settings → Permalinks to replace it.
- **Default theme locale.** The bundled `mksine` theme no longer exposes a header locale switcher; locale and text direction come from `APP_LOCALE` / `config('app.locale')`.

### Migration

1. **Themes that need plugins** — add to `theme.json`:
   ```json
   "requires": { "plugins": ["ecom"] }
   ```
   Re-discover themes (`php artisan mks:discover` or Theme Manager → Discover) after editing manifests.
2. **Fresh installs** — remove Laravel’s default `Route::get('/', …)` from `routes/web.php` so MKSine owns `/`. See [Installation §3](../01-installation.md#remove-the-default-homepage-route-from-routeswebphp).
3. **Custom themes** — if you relied on the old default `mksine` marketing homepage, use the page builder template or your own `home.blade.php`.
4. After upgrade: `php artisan optimize:clear`.

---

## 1.4.0 (2026-08-10)

### Behavior changes (non-breaking, but visible)

- **WordPress-style admin sidebar.** Labeled groups with multiple children show a hover flyout on desktop instead of expanding inline. Solo groups act as a single top-level destination. Ungrouped items (Dashboard, Plugins, Settings) remain normal leaves.
- **Locale-stable navigation groups.** Prefer `AdminNavigationGroup` / `AdminSidebarNavigation::case('…')` from `getNavigationGroup()` so icons and labels stay correct when the panel locale changes.

### Migration

1. Update plugin/theme Filament resources that return translated group strings to return `AdminNavigationGroup` (or `AdminSidebarNavigation::case()`).
2. After upgrading from source/vendor assets, run `php artisan filament:assets` (and rebuild `packages/mksine` styles if you develop the package tree).
3. Smoke-test `/admin` sidebar in both LTR and RTL locales (hover parents, click parents, collapsed icon rail).

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
- **ZIP updater + Composer core.** Project plugins and themes can be updated from ZIP (Filament + `mks-plugin:update` / `mks:theme-update`, plus matching `*:rollback`). Core `miran/mksine` is updated with Composer (`composer update miran/mksine` or `php artisan mksine:update`); ZIP replacement of `vendor/` or `packages/mksine` is not supported. `mks:release-archive` remains a **full-app** deploy artifact, not a core-only ZIP. See [Operations → ZIP updater](../operations/zip-updater.md) and `updater.*` in [configuration](../reference/configuration.md#updater). Gated by Shield super-admin in the UI and `config('mksine.updater.enabled')` (UI and CLI).
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
