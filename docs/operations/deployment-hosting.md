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

On shared hosting (including many **DirectAdmin** setups), the default directory is often `public_html`. Typical fixes:

- Ask the host to set the domain’s document root to **`.../public`** (or `public_html/public` if the app lives under `public_html`).
- Or place **only** the contents of Laravel’s `public` folder into the web root and point Laravel’s paths at the parent (documented Laravel pattern for constrained hosts).
- Or use an allowed **symlink** from `public_html` to `public` if the provider permits it.

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

[`mks:release-archive`](../../src/Console/Commands/ReleaseArchiveCommand.php) can run `npm run build` in discovered roots, then zips the project while **excluding most of `public/`** except an allowlist.

Allowed under `public/` in the archive (see [`ReleaseArchiveBuildRoots::isPublicPathAllowed`](../../src/Support/ReleaseArchiveBuildRoots.php)):

- `build/`, `themes/`, `vendor/mksine/`, `css/`, `js/`, `fonts/`, `plugins/` (under `public/`: published plugin assets from `mks-plugin:publish`, **not** the source tree).
- Root files: `index.php`, `.htaccess`, `robots.txt`, `favicon*`.

Everything else under `public/` is **omitted** from the zip. If you publish third-party assets to paths such as `public/vendor/livewire/` (outside the allowlist), they **will not** ship in the release archive unless you change packaging policy or commit them through an allowed path.

Use `--skip-build` only when outputs such as `public/build/` are already built and current; otherwise Vite manifests and hashed assets may be missing on deploy.

For the deeper command walkthrough, see [Release archive](release-archive.md).

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

After `php artisan migrate`, run **`php artisan mks:geo:import`** on the server (or import geo data in CI before shipping a database dump). Countries and states are fetched over **outbound HTTPS** from the dr5hn dataset; **cities** additionally require a MySQL **`locations`** database (default table `csv-cities`). Without that database, only countries and states are populated — checkout may show empty city dropdowns.

Re-import is **idempotent** (upsert by primary key). See [Geo import and legacy migration](../guides/geo/import-and-migration.md).

## Related docs

- [Troubleshooting](troubleshooting.md) — short symptom → link here.
- [Validation checklist](validation-checklist.md) — production web checks.
- [Commands reference](../reference/commands.md) — `mks:release-archive`.
- [Release archive](release-archive.md) — packaging deep dive.
