---
title: Deployment and hosting
description: Web server, document root, Livewire JS, and release archive expectations for production.
order: 0
---

# Deployment and hosting

How the **host Laravel application** must be wired for production, and how that differs from what `miran/mksine` controls. Use this when routes such as `/livewire/livewire.js` return **404 from the web server** (often nginx) instead of being handled by Laravel.

## Host application vs MKSine package

| Concern | Owned by host app | Owned by MKSine package |
|--------|-------------------|-------------------------|
| Web server, TLS, document root | Yes | No |
| `.env`, `APP_URL`, `APP_DEBUG`, `config:cache` | Yes | No |
| Registering `MksinePlugin` on the Filament panel | Yes | Provides the plugin class |
| Vite / `npm run build`, `public/build`, app front-end assets | Yes | No (except published theme/plugin paths you choose) |
| Plugin root path (`config('mksine.plugins_path')`), Composer at app root | Yes | Defines discovery/install contracts |
| `mks-plugin:*`, `mks:discover`, CMS tables, hook sync semantics | — | Yes |

**Rule of thumb:** if the error page shows **nginx** or **Apache** branding for a URL that has **no physical file** (Livewire scripts, named Laravel routes), the problem is almost always **server routing / document root**, not a missing MKSine file.

## Document root must be Laravel `public`

The HTTP document root must be the directory that contains **`index.php`** and **`.htaccess`** from Laravel (`public/`), not the repository root above it.

On shared hosting (cPanel, DirectAdmin) the web root is usually a fixed `public_html`. Options, best first:

- Set the domain's document root to the project's **`public/`** (many hosts allow this for addon domains and subdomains).
- Use the **split layout**: put the application next to `public_html` (e.g. `/home/user/mksine`) and only the contents of `public/` inside `public_html`. Build it with `php artisan mks:release-archive --layout=split`, which generates a `public_html/index.php` that loads `../mksine` and calls `$app->usePublicPath(__DIR__)`. Set **`MKSINE_PUBLIC_PATH=../public_html`** in `.env` so artisan commands (asset publishing, `storage:link`) write there too. MKSine also moves disk roots and `filesystems.links` that were built from the old `public/` path.
- Do **not** unpack the whole project inside `public_html` and rely on `.htaccess` rewrites: if rewriting ever fails, `.env` becomes downloadable.

If the whole repo is unpacked so that `public` is only a subdirectory and the server does not rewrite to `index.php`, **virtual asset URLs will 404**.

## Apache vs nginx

**Apache:** Laravel relies on `public/.htaccess` and **`mod_rewrite`**. `AllowOverride` must permit those rules. If rewrites are disabled, you get the same class of 404s as on misconfigured nginx.

**nginx:** `.htaccess` is **ignored**. You need an explicit front-controller pattern, for example:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

with `root` pointing at **`public`**. The `location ~ \.php$` block must pass the correct `SCRIPT_FILENAME` to PHP-FPM.

**Symptom:** opening `/livewire/livewire.js` shows a **404 page signed by nginx** while Laravel never runs — the request never reaches `public/index.php`. Fix nginx (or the reverse proxy in front of Apache) first.

## Livewire JavaScript route and `APP_DEBUG`

Livewire registers either **`/livewire/livewire.js`** or **`/livewire/livewire.min.js`** depending on `config('app.debug')` (non-minified when debug is true). Blade output should match; mismatches occur when:

- `.env` on the server differs from what was baked into **`php artisan config:cache`**, or
- Cached HTML or a CDN serves an old script URL.

After changing `.env` in production, run `php artisan config:clear` or rebuild config cache intentionally. A 404 from nginx for that URL almost always indicates the routing problem above, not Livewire itself.

## `mks:release-archive` and `public/`

[`mks:release-archive`](../../src/Console/Commands/ReleaseArchiveCommand.php) builds assets and zips the project. By default the zip is a **full** archive (core, plugins, themes) for fresh installs; `--core-only` leaves plugins and themes out for updates.

Only an allowlist of `public/` ships (see [`ReleaseArchiveBuildRoots::isPublicPathAllowed`](../../src/Support/ReleaseArchiveBuildRoots.php)): `build/`, `vendor/mksine/`, `css/`, `js/`, `fonts/`, plus `themes/` and `plugins/` in full archives, and the root files `index.php`, `.htaccess`, `robots.txt`, `favicon*`. Assets published elsewhere under `public/` (for example `public/vendor/livewire/`) **will not** ship unless you change the allowlist.

Use `--skip-build` only when `public/build/` and the plugin and theme `dist/` folders are already built and current.

For layouts (`single` or `split`), `--no-dev` and the full exclusion list, see [Release archive](release-archive.md).

## Server configuration helpers

- `php artisan mksine:doctor` checks PHP version, extensions, writable paths, disabled functions (`exec`, `proc_open`, `symlink`), the public path and when the scheduler last ran.
- `php artisan mksine:server-config [nginx|apache|supervisor|cron]` prints a ready-to-paste Nginx or Apache virtual host rooted at the current public path, a Supervisor queue worker and the cron line.

## Caches in production

After deploy:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Avoid `route:cache` if any plugin or theme registers routes inside a closure (Laravel cannot serialize closures). Most of MKSine’s admin routes are class-based and route-cache compatible; verify per release in the [Validation checklist](validation-checklist.md).

## Geo data on production servers

After `php artisan migrate`, run **`php artisan mks:geo:import`** on the server (or import geo data in CI before shipping a database dump). Countries, states, and cities are fetched over **outbound HTTPS** from the dr5hn dataset; no extra database is needed. For one market, `php artisan mks:geo:import --country=IR` runs in the command (no queue worker) and exits with failure if anything was not imported. Without `--country` or `--sync` the import runs on the queue: start `php artisan queue:work --memory=1024`.

Re-import is **idempotent** (upsert by primary key). See [Geo import and legacy migration](../guides/geo/import-and-migration.md).

## Related docs

- [Troubleshooting](troubleshooting.md) — short symptom → link here.
- [Validation checklist](validation-checklist.md) — production web checks.
- [Commands reference](../reference/commands.md) — `mks:release-archive`.
- [Release archive](release-archive.md) — packaging deep dive.
