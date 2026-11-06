# Changelog

All notable changes to `mksine` will be documented in this file.

See [`docs/meta/upgrade-guide.md`](docs/meta/upgrade-guide.md) for migration notes per release and [`docs/meta/versioning.md`](docs/meta/versioning.md) for the semver policy.

## Unreleased

- (none)

## 1.23.1 - 2026-11-06

### Changed

- **The quick installer is now `mksine.php`** (was `mksine-installer.php`) and opens at `https://your-domain/mksine.php`. It no longer asks for a setup code: the browser that clicks *Download and install* owns the run, and another browser can take over after 10 minutes without activity. Nothing is written to the server before the run starts.
- **`/install` no longer asks for the setup code** in the browser that chooses the language first (a form post, so crawlers never claim it). The code from `SETUP-TOKEN.txt` is asked for only when a second browser tries to continue, and the file is created only then. Pages after the language step send an unclaimed visitor back to the language choice.

## 1.23.0 - 2026-11-06

### Added

- **Quick installer (`mksine-installer.php`).** One small file, published with every release, that users upload to their web root instead of the zip. After the setup code from `SETUP-TOKEN.txt`, it checks the server, downloads the newest release from GitHub in time-limited parts (resumed with HTTP `Range`), verifies its SHA-256, checks every zip entry, extracts in batches, keeps the host's `.htaccess` lines, renames placeholder pages, sets `MKSINE_PUBLIC_PATH` for the real web folder and deletes itself. It picks the shared-hosting layout (`../mksine`) or, for an otherwise empty folder around a `public` web root, the server layout. Hosts that block downloads can upload the zip next to it. Written in PHP 7.0 syntax so an old PHP shows a readable message.
- **`POST /install/handoff`.** A one-time code from the quick installer (stored as a SHA-256 hash, valid 30 minutes, deleted on use) opens the web installer without entering the setup code twice.
- `mks:release-build` writes the version-stamped `mksine-installer.php` and lists it under `installer` in `release.json` and in `SHA256SUMS`; `scripts/publish-release.sh` uploads it.

## 1.22.1 - 2026-11-06

### Fixed

- **Release zips contained the maintainer's own project.** `mks:release-build` zipped the development application, so 1.22.0 downloads included editor and AI-tool folders (`.cursor`, `.ai`, `.agents`, `.specs`, `.mksflow`, `graphify-out`), internal notes, a project theme and project-specific code, at 123–140 MB. It now builds a clean Laravel application with MKSine and the plugins in a temporary folder, packs only the application folders, strips docs from `vendor/` and plugins, and never ships `composer.lock`. Downloads are about 44 MB. `--no-dev` is gone (production dependencies are always used); `--laravel`, `--skip-build` and `--keep-staging` were added.
- **Web installer.** After a failed step, *Back* reappears so the site details or plugin choice can be changed before retrying. Long paths on the requirements page wrap instead of widening the page.

## 1.22.0 - 2026-11-05

### Added

- **Install pipeline.** `mksine:install` runs ordered, repeatable steps (requirements, `.env`, user model, admin panel, publishing, migrations, assets, theme, plugins, permissions, hooks, admin, sample content, locale, runtime, finish), stops at the first failure with a non-zero exit code and continues with `--resume`. New options: `--profile`, `--theme`, `--plugins`, `--locale`, `--seed-demo`, `--app-url`, `--db-*`. It registers `MksinePlugin` on the admin panel and removes Laravel's welcome route.
- **Web installer at `/install`** for hosts without a terminal: language, a security code from `SETUP-TOKEN.txt`, requirements, database test, site and admin, then one install step per request. It closes itself when installation finishes.
- **Shared hosting support.** `MKSINE_PUBLIC_PATH` for a web root outside the project; migrations run in chunks; the scheduler drains the `database` queue on shared installs; the admin console is hidden where `proc_open` is disabled.
- **System health page** (System → System health) for cron, queue, requirements, environment and risky settings.
- **`mksine:doctor`**, **`mksine:server-config`** and **`mks:release-build`** (shared-hosting and server zips, `SHA256SUMS`, `release.json`), plus `scripts/publish-release.sh` for GitHub Releases.

## 1.21.0 - 2026-11-04

### Added

- **`mksine:install` asks for a GitHub star.** At the end of an interactive install, the installer asks whether you want to star MKSine on GitHub and opens the repository in your browser if you agree. It does not ask with `--no-interaction`, in CI, or in tests. If the browser cannot be opened, the link is printed instead.

### Fixed

- **The console status panel listed only some scheduled tasks.** Plugins usually register their schedules only when `runningInConsole()`, so a web request could not see them (for example `mks-import-export:run` and `mks-analytics:prune`). The task list now comes from `php artisan schedule:list --json`, cached for 10 minutes, with the in-process schedule as a fallback. Last-run results are matched by task name, so they line up with that list.

### Changed

- **README.** New GIFs and screenshots of the current admin (page builder, menus, console, plugins, themes, settings, admin menu, translations, media) are stored in `docs/assets/readme/` instead of an external host. The feature texts now cover marketplace installs, rollback, SEO analysis, the admin menu editor, the Jalali calendar, and the console status panel.

## 1.20.0 - 2026-11-04

### Added

- **Redesigned admin console.** One **Run** button replaces *Start* and *Run once*: every command runs in the background with live output, and every finished run is saved to the history, including long-running workers. Output keeps its ANSI colors and progress bars. The prompt has autocomplete for allowlisted commands (with descriptions), `↑`/`↓` command history, `Ctrl+C` to stop, `Ctrl+L` to clear, and one-click chips from the new `console_terminal.quick_commands` config key plus `daemon_presets`. The history panel can be filtered, opens a saved run in the terminal, and runs it again. The history list no longer loads every run's full output.
- **System status panel in the console.** Shows whether the scheduler runs (heartbeat on every `schedule:run`, with the cron line to add when it does not), the scheduled tasks with their next and last run, live `queue:work` workers (heartbeat from queue events), waiting and failed jobs with the age of the oldest waiting job, and background processes started from the console. Actions run in the terminal: run the scheduler, start `schedule:work` or a worker, `queue:restart`, retry or list failed jobs.
- **Run in background.** A running command can be detached from the terminal and keeps running. You can view or stop it again from the status panel.

### Fixed

- **Background commands never started on macOS.** The runner was launched with `setsid`, which macOS does not have, and with `nohup`, which fails under launchd-managed PHP-FPM (Herd). Processes stayed in *Starting* forever. The runner is now detached with `trap '' HUP` and uses `setsid` only where it exists. Runner errors are written to the process output instead of `/dev/null`.
- **Starting a worker from the panel blocked the web request.** The whole `cd … && …` list was backgrounded, so a subshell held PHP's output pipe until the command ended. Only the runner is backgrounded now.
- **Stop now stops the command.** The runner forwards `SIGTERM` to the command, waits for it, and records the run as *Stopped*. Before, the command could keep running after its runner was killed. Stopping a process before its runner started no longer lets it start, and `stop()` reads the current PID instead of the caller's stale copy.
- **A process that never starts or whose runner dies is shown as failed.** A process still *Starting* after 20 seconds is marked failed with a message, and a running process whose runner died is *Failed* instead of *Completed*.
- **`AdminConsoleProcessManager` container binding.** The service provider bound the class without importing it, so the binding used a class name that does not exist.

## 1.19.1 - 2026-11-03

### Fixed

- **The admin panel ignored `APP_LOCALE`.** Language Switch 5 asks the browser for a language before it reads `app.locale`, and Symfony returns the first available locale (`en`) when the browser sends no language or one the site does not have. A site with `APP_LOCALE=fa` therefore showed English to English and German browsers, and to clients without an `Accept-Language` header. The panel now uses, in order: the language picked in the switcher, the locale saved on the user (`HasLocalePreference`), the switcher's remembered cookie, and then `APP_LOCALE`. The browser language is used only when `APP_LOCALE` is not one of the panel's languages.
- **Panel Livewire requests use the chosen language.** `SwitchLanguageLocale` ran only on full panel page loads. On `livewire/update` requests (form saves, modals, table filters, notifications), the panel's serving callbacks and middleware ran in `APP_LOCALE` until the component restored its own locale. It is now registered as Livewire persistent middleware, like Filament's own panel middleware.

## 1.19.0 - 2026-11-03

### Fixed

- **`mks:geo:import` imported no cities on servers and still said “completed”.** Cities were read from a private MySQL `locations` database that is not shipped with the package. When it was missing, the import logged a warning and reported success. Cities now come from the dr5hn per-country file `cities/{ISO2}.json`, which the import already downloaded for translations, and the list of countries comes from `geo_countries`. The `locations` table is read only when you pass `--locations-database`; if it cannot be read, the command fails with that error.
- **Cities with an unknown state no longer break a whole country.** Rows whose `state_id` is not in `geo_states` caused a foreign key error, so no cities were imported for AO, ET, IT, KR, LU, and LV. These rows are now skipped and counted per country in the log, and the rest of the country is imported.
- **`--only=cities` without states gives a clear message.** It used to fail with a raw SQL error. It now says which country has no states and tells you to run `--only=states` first.
- **Honest results.** With `--sync` or `--country`, the command prints how many countries, states, and cities were imported and how many cities were skipped. It exits with failure, and does not print “completed”, when a country failed. Skipped cities are a warning: the command prints “completed with N cities skipped” and exits 0. On the queue, the batch’s final log line names the number of failed country jobs and says “completed” only when none failed, followed by the skipped-city count if any. A failed download of `countries.json` or `states.json` now fails the import instead of importing 0 rows.

### Changed

- **`mks:geo:import --country=XX` runs without the queue.** One country takes seconds (Iran about 40 seconds), so it no longer waits for a worker. A global import without `--sync` still uses the queue, and the command now prints `php artisan queue:work --memory=1024` and the `--sync --country=IR` alternative, because the default 128 MB worker limit stops after the first country.
- **`--locations-database` has no default.** To keep reading cities from MySQL, pass `--locations-database=locations`. Ids are unchanged either way: both sources use dr5hn ids and the import upserts by id, so ecom addresses that reference `geo_states` and `geo_cities` stay valid.

## 1.18.0 - 2026-11-03

### Fixed

- **Missing user profile columns on new projects.** `Models\User` and the user form use `bio`, `date_of_birth`, `phone_country_code`, and `phone_number`, but no package migration created them, so projects failed with `Unknown column 'phone_number'`. The new migration `2026_11_03_100000_add_profile_columns_to_users_table` adds each column only if it is missing. Existing columns keep their length and data, and rollback removes nothing. Run `php artisan mksine:update`, or publish with `vendor:publish --tag=mksine-migrations` and run `php artisan migrate`.

## 1.17.1 - 2026-11-01

### Fixed

- **Jalali picker on newer Filament 5 releases.** The picker failed with `handleFocusOut is not defined` and `this.$refs.panel.toggle is not a function` on Filament versions that changed their date picker. It now has its own Blade view, CSS, and panel positioning, so it no longer depends on Filament's picker internals. Run `php artisan filament:assets` after updating.

## 1.17.0 - 2026-11-01

### Added

- **Jalali (Shamsi) support for Filament in core.** `TextColumn` and `TextEntry` get `jalaliDate()` and `jalaliDateTime()`, and `DateTimePicker` and `DatePicker` get `jalali()`, with no Composer or npm package. When **Settings → General** uses the Shamsi calendar, every `DateTimePicker` and `DatePicker` in the panel uses it automatically. The picker uses Filament's panel markup with an Alpine component from core and still stores Gregorian values. `Miran\Mksine\Support\JalaliCalendar` provides the conversion and formatting. Run `php artisan filament:assets` after updating.

## 1.16.0 - 2026-10-31

### Added

- **Named hook handlers.** `Hooks::addFilter()`, `HookFilterRegistry::add()`, `ThemeActionManager::addAction()`, and `theme_add_action()` take an optional last `?string $id`. Adding an id that already exists on the hook replaces that entry, so a double boot does not run a handler twice. Without an id, nothing changes.
- **Hook removal.** `Hooks::removeFilter()`, `HookFilterRegistry::remove()`, `ThemeActionManager::removeAction()`, and `theme_remove_action()` remove a handler by id, string callable, or callable reference. Removing by id also blocks a later add with that id on that hook, so removal does not depend on plugin boot order. Removing something that does not exist does nothing. See [Removing hooks](docs/guides/hooks/runtime-registration.md#removing-hooks).
- **`Hooks::hasFilter()` and `HookFilterRegistry::has()`.** They report whether a filter hook has any handler.

### Changed

- **Theme action errors are reported.** When a callback throws in `ThemeActionManager::doAction()`, the exception now goes to Laravel's `report()` as well. The page still renders, the callback's partial output is still dropped, and the `app.debug` comment is unchanged. A failure while reporting is ignored. Filters are unchanged: an exception in a filter still propagates.

### Documentation

- **Theme action argument names.** `theme_do_action()` spreads `$args` as named arguments, so a callback must declare matching parameter names or a variadic. This is existing behaviour, now documented in the `theme_add_action()` docblock and in [Views and layouts](docs/guides/themes/views-and-layouts.md#argument-names).

## 1.15.0 - 2026-10-28

### Added

- **Admin menu editor.** **Settings → Admin menu** lets a Super Admin hide sidebar items, move them to another group or into the Settings sub-navigation, change the order and name of items and groups, and hide whole groups. Changes are stored as JSON in the `mksine_admin_menu` setting. With no setting, the menu is unchanged. **Reset to default** deletes the setting. Hidden and moved items keep their URL and `canAccess()`. See [Admin menu editor](docs/guides/settings/admin-menu.md).
- **`mksine.admin.navigation.groups` filter.** Plugins receive `list<NavigationGroup>` and the panel, and return the groups. It runs after Filament builds the navigation and before the super-admin overrides, only on panels that use `MksinePlugin`.

### Changed

- **MKSine binds Filament's `NavigationManager`.** `MksinePlugin` registers `MksineNavigationManager`. Panels without the plugin get Filament's navigation unchanged.
- **Media sidebar items have stable keys.** The library item is keyed `MediaResource::class` and the upload item `MediaResource::class . '@create'`, instead of their translated labels. The sidebar looks the same.
- **Keyless sidebar items are keyed by URL.** On panels that use `MksinePlugin`, a `NavigationItem` without `->key(...)` gets `url:{path}` as its key instead of Filament's translated-label fallback, so menu overrides survive a locale change.

## 1.14.0 - 2026-10-17

### Added

- **Public page document for headless frontends.** `GET /api/mksine/pages`, `GET /api/mksine/pages/blocks`, and `GET /api/mksine/pages/{slug}` return schema version 1. A React, Vue, Angular, or Next.js app renders `page.blocks` itself. Blade themes are unchanged. See [Headless page document](docs/guides/page-builder/headless.md).

### Changed

- **Block appearance is its own editor tab.** The component editor opens on Content and Block appearance. A region edit still hides appearance. Saved block JSON is unchanged.
- **Package dev dependencies target the current test stack.** `require-dev` is Pest 4, Testbench 10, Collision 8, and Larastan 3. Production `require` is unchanged, including `php: ^8.2`.

## 1.13.0 - 2026-10-12

### Added

- **Page builder canvas, signed preview, and slide-over settings.** Authors edit on the canvas. The preview is a signed iframe of the unsaved tree (`mksine.page-builder.preview`, auth, 10-minute cache). Block settings open in a slide-over. The component library can be filtered in the browser. Desktop, tablet, and mobile only change the frame width. An empty page is one add row and does not mount the iframe. Nested insert, move, duplicate, and remove pass the parent block and region index.

- **Shared block appearance and a package stylesheet.** Every block can store alignment, colors, border, padding, and spacing beside its data (`BlockPresentation`). Default blocks render with `mksine-blocks.css`, scoped to `.mksine-block`, so they do not depend on the active theme's Tailwind build. Rebuild that file with the Tailwind CLI and `php bin/scope-blocks-css.php`.

- **Card block.** `card` is a layout block with header, body, and footer drop zones. Header and footer can be hidden without deleting the blocks inside them. Colors, border, radius, shadow, alignment, and padding are an allowlist.

- **Saved sections and builder revisions.** `page_builder_sections` stores a reusable payload, placed with the `section_ref` block. `page_builder_revisions` stores a payload snapshot per page. Session undo and redo are not those rows.

- **Named edit regions and inline headings.** A region save merges into the block and leaves the other fields in place. Heading text can be edited on the canvas.

- **Payload checks before preview and save.** `PayloadNormalizer` drops unregistered block types, checks the stored shape, and allowlists heading tags and link protocols. Media used by a render is loaded with one `whereIn`.

### Changed

- **The page builder no longer uses a structure list or a left navigator.** The canvas is the editor. `show_page_header` and `builder_content_width` are editable on builder pages. Switching a new page to `builder` seeds an empty payload, a hidden page header when that flag is null, and full content width when the width is blank.

### Fixed

- **Dragging a block no longer opens its editor.** A move message identifies the block being dragged separately from an edit. Reordering inside the same list, and editing a block nested in columns, grid, or an inset, keeps the parent and region index.

## 1.12.0 - 2026-10-06

### Added

- **Plugins can register their own Filament panel, panel access, and hook listeners without host hardcoding.** Optional `filament_panel_provider` in `plugin.php` is registered during package `register()` for every discovered plugin (active state is not consulted; hiding the panel when a plugin is inactive is not this layer’s job). `InteractsWithMksine::canAccessPanel()` asks the `mksine.user.can_access_panel` filter first and keeps the existing Shield checks unless a listener returns a bool. `mks:discover` also scans each plugin’s `src/Hooks/Listeners` when that directory exists. `hooks.discovery_paths` is unchanged.

### Security

- **Marketplace ZIPs are authenticated, not just checksummed** — `archive_sha256` came from the same catalog JSON as `download_url`, so a compromised mksine.com could ship both a malicious ZIP and a matching hash. Listings now carry `archive_signature` (Ed25519 over `mksine-marketplace-v1`, kind, package_id, version, sha256). The public key ships in `resources/keys/*.ed25519.pub`; extra keys can be added via `mksine.marketplace.signing_public_keys`. Verification runs when the listing is parsed and again against the bytes on disk. Catalog and download HTTP no longer follow redirects (`withoutRedirecting()`), so host pinning cannot be skipped with a 302. A signature that is present is always checked. `require_release_signature` defaults off until the catalog API sends `archive_signature`; the live directory omits it, and requiring it hid every listing behind the Add from MKSine error. Set `MKS_MARKETPLACE_REQUIRE_RELEASE_SIGNATURE=true` once the API signs. Operators sign with `php artisan mksine:sign-marketplace-release` and an offline `.sec` file — the secret is never printed and is gitignored.

- **Public comments can no longer be attached to arbitrary Eloquent models, and inserts are rate-limited** — `PostComments` stored `commentableType` / `commentableId` as unlocked public properties, and the only check was `is_subclass_of(..., Model::class)`. Combined with a fail-open `AllowsPublicComments` gate (skipped when the model did not implement the interface), a visitor could retarget the form at `User` or an order and insert 5 000-character rows with no throttle. Both properties are now `#[Locked]`, the type must be in `mksine.commentable_types` *and* implement `AllowsPublicComments`, `allowsPublicComments()` is required (fail closed), and `RateLimiter::attempt()` caps submissions per IP (`mksine.comments.max_per_minute`, default 5 / 60s). `ContentTypeRegistry` is not the allowlist: Post is reserved out of that registry, and ecom comments target Product, which is not a CPT.

- **Media file metadata is no longer writable from the edit form** — `file_name`, `mime_type`, `size`, `width`, `height`, `path` and `url` were `disabled()` *and* `dehydrated()`, so Livewire still saved whatever the client put in form state. All seven are in `Media::$fillable`, and `mutateFormDataBeforeSave` preferred the submitted value over `mime_content_type()`. Anyone with `Update:Media` could upload an SVG, label it `image/png`, and walk it past the picker mime filter. Those fields are now `dehydrated(false)`, and both create and save run `Miran\Mksine\Support\MediaStoredFile`, which forgets the client values and re-reads mime, size, dimensions, path and URL from the file on disk. Path segments containing `..` are rejected.

- **SVG is no longer an allowed media type, and scriptable SVGs are rejected everywhere** — media is served from the site's own origin, so an uploaded `<svg onload="…">` was stored XSS against every visitor who opened it, and the only check was a mime allowlist that `image/svg+xml` was on by default. `image/svg+xml` is off the default `mksine.media.allowed_types`, and both upload paths (the media picker and the Media resource form) now run the file through `Miran\Mksine\Support\SvgSafety`, which parses the document with libxml and refuses scripts, `on*` handlers, `foreignObject`, animation elements that can retarget `href`, script URIs, remote `<use>`, `@import`, and internal DTD subsets. The check keys off the filename extension as well as the detected mime, because the web server picks the content type from the extension the file lands on disk with.

- **Theme and plugin screenshots are validated and served inert** — an uploaded archive could ship `screenshot.svg` containing a script, and both routes served it with `Content-Type: image/svg+xml` into an admin's session. Screenshots now go through `Miran\Mksine\Http\Responses\ScreenshotResponse`, which 404s an unsafe SVG and adds `X-Content-Type-Options: nosniff` and `Content-Security-Policy: default-src 'none'; …; sandbox`.

- **Livewire's temporary-upload preview no longer serves SVG** — `preview_mimes` included `svg`, and the preview route streams the *temporary* file back on this origin with its own content type before any validation rule runs, so it handed out a signed same-origin XSS URL for any SVG that reached the upload endpoint, regardless of the media allowlist. SVG uploads lose their inline preview.

- **Plugin ZIP upload no longer executes the archive** — the uploaded `plugin.php` was written to a temp file and `require`d to read its `id`, which handed arbitrary code execution to anyone who could reach the upload form, before any validation ran. The manifest is now read statically by `Miran\Mksine\Core\Plugins\PluginManifestSource` (tokeniser, no execution), so a rejected archive never runs.

- **Plugin and theme archives can no longer escape their install root** — the manifest `id`, the `theme.json` `name`, and the archive's root folder were interpolated straight into the target path, so `'id' => '../../public'` wrote outside `plugins/`. Identifiers are validated against `Miran\Mksine\Support\PackageIdentifier` and archives are unpacked through `ArchiveExtractor`, which rejects traversal entries, symlinks and zip bombs.

- **Code-mutating plugin and theme actions require Super Admin** — upload, install, activate, deactivate, uninstall and delete on the Plugins and Themes pages, plus the per-theme Custom CSS/JS editor, now call `SuperAdminGate::authorize()`. Previously any role with page access could install and activate plugin code.

- **Theme custom CSS/JS paths are confined to their storage directory** — `ThemeManager::getCustomStoragePath()` and `getExtraAssetsStoragePath()` interpolated an unvalidated identifier, allowing any `.css`/`.js` file on disk to be overwritten. The Custom CSS/JS modal now only accepts identifiers of discovered themes.

- **The media picker enforces the Media policy** — `MediaPickerModal` had no authorization at all, and it renders on every panel page, so any authenticated panel user could enumerate the whole media library and upload files. `open()`, `toggleSelection()`, `confirm()` and the detail lookup now require `viewAny` on `Media`, and `uploadFiles()` requires `create`. Rendering degrades to an empty list instead of a 403 so an unauthorised user does not take the surrounding page down.

- **The admin terminal runs an allowlist** — the parser accepted any `php artisan` or `composer` sub-command, so `artisan tinker --execute` turned a stolen Super Admin session into a shell. Sub-commands are matched against `mksine.console_terminal.allowed_commands`; `tinker`, `db:seed`, `serve`, `env`, `composer exec`, `composer run-script` and `mksine:fresh-super-admin` are not on the default list. Set a runner to `['*']` to restore the old behaviour.

- **The Super Admin role can no longer be self-granted** — the user form listed every role, so any role holding `Update:User` could tick super admin on its own account, or reset an existing Super Admin's password. Protected roles are hidden from the options (and therefore from Filament's derived `in` rule), and editing, deleting, restoring or force-deleting a Super Admin is reserved for Super Admins.

### Changed

- **Marketplace catalog cards** — Add from MKSine shows three plugin cards per row on large screens, and the screenshot uses its own aspect ratio instead of a cropped 16:10 cover. Browse timeouts default to 15s / 5s.

### Fixed

- **Plugin screenshot path check** — `PluginManifest::screenshotAbsolutePath()` used the same prefix comparison without a trailing separator, so a sibling of the plugin directory counted as inside it. It now uses `FilesystemPath::isWithin()`, as does the theme screenshot route.

- **Plugin and theme delete path checks** — the containment check compared without a trailing separator, so a sibling directory such as `plugins-backup` passed as "inside `plugins`". Both now use `FilesystemPath::isWithin()`.

## 1.11.1 - 2026-09-20

### Fixed

- **Admin CSS on production** — Panel styles prefer the Filament public file (`/css/miran/mksine/mksine-styles.css`) and copy the package dist there when it is stale. The `/mksine/admin-styles.css` route is a fallback. Catalog tab icons have an inline size cap so a missing stylesheet cannot blow SVGs up to 300×150.

## 1.11.0 - 2026-09-20

### Changed

- **Marketplace plugin cards** — Add from MKSine matches the public directory card: 16:10 cover, title, summary, author, stats, then category · version · license. Install / Installed / Update stay on the card; covers are not cropped to a 72px icon.

- **Admin CSS after upgrade** — Panel styles load from `/mksine/admin-styles.css` (package `resources/dist/mksine.css`) with a filemtime cache buster. Catalog HTTP cache keys include `mksine.version`. After `composer update miran/mksine`, `php artisan optimize:clear` is enough; `filament:assets` is not required for this CSS.

## 1.10.0 - 2026-09-20

### Changed

- **Marketplace catalog tabs** — Installed ↔ Add from MKSine stays on Livewire tabs. The catalog ZIP list is fetched once, cached (`marketplace.cache_seconds` / `cache_stale_seconds`), and kept when you leave the tab. HTTP browse timeouts default to 6s/2s and retry only on connection errors.

- **Marketplace plugin catalog** — Add from MKSine uses directory-style cards (icon, summary, author, last published, stars, downloads) instead of a stacked list. Catalog JSON `downloads`, `rating_average`, and `rating_count` are now read. Empty ratings/downloads stay hidden; there is no fabricated “active installs” or compatibility check. Installed listings use a green card and check chip; available listings keep a filled Install now button.

## 1.9.0 - 2026-09-18

### Added

- **Plugin migrations without `migrate`** — if the host app replaced Laravel’s `migrate` command (e.g. `migrate:smart` only), plugin install still runs the plugin migration files through the migrator.
- **Content import API** — `Miran\Mksine\Core\Content\ContentImport` upserts Post/Page/Media by slug (or media url/path). Filter `mksine.import.row` may mutate or skip a row (`null`).
- **Stable event catalog** — `Miran\Mksine\Core\Hooks\SystemEventCatalog` lists HookManager event names that must not be renamed. `post.published` fires when a post is created published or transitions to published.
- **Content visibility hooks** — `mksine.content.visible` (single record) and `mksine.content.query` (list Builder) on storefront Post/Page/Entry/category/author. Default is public; Membership (and others) may hide or require login.
- **Storefront view hook** — `mksine.storefront.viewed` event plus `Hooks::filter` of the same name, fired once per public GET after Page/Post/archive/home resolve (`path`, `content_type`, `content_id`, `status`). Not fired for Livewire follow-ups or admin/API paths.
- **Storefront 404 hook** — `mksine.storefront.not_found` event plus `Hooks::filter` of the same name, fired for public GET/HEAD 404s (admin, Livewire, and API paths excluded). Filters may return a response (redirect).
- **Content slug hook** — `mksine.content.slug_changed` after Post/Page slug updates (`type`, `id`, `old`, `new`, `old_path`, `new_path`).
- **Plugin screenshots** — optional `screenshot` in `plugin.php` (PNG, JPG, GIF, WebP, or SVG). Admin Plugins list and `/mksine/plugin/{id}/screenshot` read the file from the plugin directory; `mks-plugin:publish` copies it when present.
- **Marketplace install** — Plugins and Theme Manager **Add from MKSine** lists the official JSON catalog (`/api/marketplace/v1/{plugins|themes}`), downloads the listing ZIP, and installs it the same way as a local ZIP upload. New config: `marketplace.api_url`, `timeout`, `connect_timeout`, `download_timeout`. Super-admin can update a project plugin/theme when the catalog version is newer. Install/update stays on the catalog tab. Failed fetches have Retry. Listings show license, changelog, and a link to the public directory page.

## 1.8.7 - 2026-09-16

### Fixed

- **MediaPicker gallery drag** — Sortable’s fallback clone is ignored by Alpine (`x-ignore`) so dragging a thumb no longer throws `media is not defined`.
- **Media library modal** — Browse Files no longer reserves an idle spinner slot; Upload keeps the spinner and label on one line.

## 1.8.6 - 2026-09-16

### Fixed

- **MediaPicker gallery RTL** — SortableJS uses physical `left`, so the selected-thumbs grid is `dir="ltr"` and drag-and-drop matches LTR; the rest of the form stays RTL.

## 1.8.5 - 2026-09-16

### Fixed

- **MediaPicker gallery RTL** — drag-and-drop reorder no longer inverts insert side on a right-to-left grid; LTR is unchanged.

## 1.8.4 - 2026-09-16

### Fixed

- **MediaPicker gallery** — selected thumbs sit three per row; drag-and-drop reorder works (Sortable `forceFallback`); left/right arrows swap items even when IDs are strings.
- **Media library modal** — All Types chevron uses logical `end` (RTL); Browse Files stays next to the dropzone copy and shows loading while files upload; choosing a thumb no longer re-renders the whole modal.
- **Page builder toolbar** — Use Template and Add Component no longer reserve an empty spinner slot; the spinner replaces the icon only while the request runs.

## 1.8.3 - 2026-09-15

### Fixed

- **MediaPicker dist CSS** — rebuild `resources/dist/mksine.css` so compact gallery thumbs (`w-28`) are in the published stylesheet.

## 1.8.2 - 2026-09-15

### Fixed

- **MediaPicker modal focus** — closing or confirming no longer leaves focus inside a hidden dialog (Chrome `aria-hidden` warning). Focus returns to the opener.
- **MediaPicker gallery layout** — selected thumbs stay compact (`w-28` wrap) instead of stretching full width in a sidebar; reorder controls are centered under each image.

## 1.8.1 - 2026-09-15

### Fixed

- **MediaPicker gallery chrome** — drag handle and move-earlier/later controls sit under each thumbnail instead of covering the image; selected tiles keep a minimum width so the grid stays readable.

## 1.8.0 - 2026-09-15

### Added

- **Gallery reorder** — `media_attachments.sort_order` (backfilled from `id`); `MediaPicker::multiple()` is reorderable by default (drag + keyboard). `HasMediaAttachments::getMediaCollection()` / `getOrderedMedia()` return editor order. See [Media library](docs/guides/media/library.md).
- **SEO analysis** — advisory Yoast-like scorer (`SeoAnalyzer`) and Filament `SeoAnalysis` field on Post, Page, Category, and Tag, plus `focus_keyphrase`. Plugins append checks with `Hooks::addFilter(SeoAnalyzer::FILTER_CHECKS, …)`. See [SEO analysis](docs/guides/seo/analysis.md).

## 1.7.0 - 2026-09-15

### Added

- **MediaPicker video and audio** — picker fields can accept `video/*` and `audio/*`; the library grid, uploads, and selected chips preview A/V. Default `acceptedFileTypes` stays `['image/*']` so existing image fields are unchanged. Uploads are intersected with `config('mksine.media.allowed_types')`.
- **Page builder Video and Audio blocks** — insert a library video or audio file with caption, controls, autoplay, and loop.
- **CKEditor media insert** — the editor picker can insert `<video>` and `<audio>` as well as images.
- **Marketplace catalog (coming soon)** — Theme Manager and Plugins have an “Add from MKSine” tab that explains in-panel install from [mksine.com](https://mksine.com) is not connected yet, with a link to the public directory. New config: `mksine.marketplace.url` / `directory_url`.

### Changed

- **Media allowlist** — `mksine.media.allowed_types` now includes `video/ogg` and audio types (`audio/mpeg`, `audio/mp4`, `audio/ogg`, `audio/wav`, `audio/x-wav`, `audio/webm`, `audio/aac`). Re-publish or merge config if you maintain a published `config/mksine.php`.

## 1.6.1 - 2026-09-14

### Fixed

- **Menu builder drag** — outdenting a middle child no longer nests remaining siblings under it; the subtree moves after those siblings (same result as PHP Out / WordPress). `indentInTree` now shares Alpine’s max depth of 4.
- **Page builder payload** — undo history is stored in session instead of the Livewire snapshot; Filament state sync is a single debounced `$wire.set` (no `.live` entangle); Sortable re-inits only when the block DOM actually changes.
- **Page builder loading** — picker and template cards show a spinner only on the clicked item, not every card.

### Changed

- Page builder editor, toolbar, block actions, and canvas show `wire:loading` feedback without changing button or canvas size.

## 1.6.0 - 2026-09-09

### Added

- **Native tags** — flat polymorphic tags on posts and pages (`tags` / `taggables`), Filament Tag resource, storefront archives at `/tags` and `/tag/{slug}`, and a `tag` menu item source. See the [upgrade guide](docs/meta/upgrade-guide.md).

## 1.5.3 - 2026-09-09

### Fixed

- **Page edit (builder)** — builder sections (page builder field and display options) now show on first load when the saved page type is `builder`, without toggling type to simple and back; `PageForm` falls back to the record type before live form state hydrates.

## 1.5.2 - 2026-09-09

### Fixed

- **Page create (builder)** — switching page type to “builder” no longer leaves `builder_content_width` null while the UI shows “Full width”; `PageForm` now sets state on type change, requires width only for builder pages, and `CreatePage` defaults blank width to `full`.

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
