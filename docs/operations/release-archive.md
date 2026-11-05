---
title: Release archive (mks:release-archive)
---

# Release archive (`mks:release-archive`)

`mks:release-archive` builds a deployable `.zip` of the whole project: code, `vendor/`, compiled assets and, by default, every plugin and theme. It exists because shared hosting (cPanel, DirectAdmin) has no `composer install` or `npm run build` on the server. It does not replace CI/CD.

There are two kinds of archive:

| Kind | Command | Contains | Use it for |
| ---- | ------- | -------- | ---------- |
| **Full** (default) | `mks:release-archive` | Core, all plugins, all themes | A fresh install. The built-in web installer (`/install`) asks which plugins to activate. |
| **Core only** | `mks:release-archive --core-only` | Core application only; plugins and themes are left out | Updating an existing site. Plugins and themes update through the [ZIP updater](zip-updater.md). |

## Publishing a release

For a public download (mksine.com), build both layouts at once:

```bash
php artisan mks:release-build --no-dev
```

This writes to `storage/app/releases/{version}/`:

| File | Purpose |
| ---- | ------- |
| `mksine-{version}-shared-hosting.zip` | Split layout (`mksine/` + `public_html/`) for cPanel and DirectAdmin |
| `mksine-{version}-server.zip` | Single layout for a VPS; point the document root at `public/` |
| `SHA256SUMS` | Checksums; verify with `shasum -a 256 -c SHA256SUMS` |
| `release.json` | Version, release date, requirements and each file's name, size and SHA-256, for the download page |

`scripts/publish-release.sh` (in the package) runs that build with `--no-dev`, verifies the checksums and attaches the files to the GitHub release for tag `v{version}`, which must already be pushed. It creates the release when it does not exist (`--draft` makes it a draft) and replaces the files when it does. It needs the GitHub CLI (`gh`) logged in. The download page reads the newest manifest from `https://github.com/MiranSalehi/mksine/releases/latest/download/release.json`.

```bash
git -C packages/mksine tag v1.22.0 && git -C packages/mksine push origin v1.22.0
packages/mksine/scripts/publish-release.sh
```

End-user installation is described in [Installing MKSine](../guides/install/install.md) ([فارسی](../guides/install/install.fa.md)).

## Command surface

```
php artisan mks:release-archive
    [--output=PATH]            # Absolute or project-relative path for the .zip
    [--core-only]              # Leave out plugins and themes
    [--layout=single|split]    # single (default): document root = public/; split: shared hosting
    [--app-dir=mksine]         # split layout: folder for the application
    [--public-dir=public_html] # split layout: folder for the public files
    [--no-dev]                 # Install production Composer dependencies in a staging copy first
    [--skip-build]             # Skip npm; only zip
    [--dry-run]                # Print build roots, output path and archive settings; change nothing
```

The default output is `storage/app/mksine-release-{timestamp}.zip`. If no build roots are found and `--skip-build` is not passed, the command fails rather than produce an under-built archive.

## Layouts

**`single`** puts the project at the zip root. Point the web server's document root at `public/` (see `php artisan mksine:server-config`).

**`split`** is for shared hosting, where the web root is a fixed folder such as `public_html` and the application must live outside it:

```
mksine/          ← the application (upload next to public_html, e.g. /home/user/mksine)
  .htaccess      ← "Require all denied", in case it is ever placed inside a web root
  .env.example   ← includes MKSINE_PUBLIC_PATH=../public_html
  vendor/ app/ config/ plugins/ ...
public_html/     ← the contents of public/
  index.php      ← generated: loads ../mksine and calls $app->usePublicPath(__DIR__)
  build/ css/ js/ themes/ plugins/ vendor/mksine/ ...
```

`MKSINE_PUBLIC_PATH` makes artisan commands (asset publishing, `storage:link`) write to `public_html/` as well, not only web requests. The split layout leaves out `packages/mksine/`: no Composer runs on the server, and autoload already points at `vendor/miran/mksine`, which is packed from the path-repository symlink.

On DirectAdmin the web root is `domains/{domain}/public_html`; upload `mksine/` to `domains/{domain}/mksine` so `../mksine` still resolves.

## Build roots

`ReleaseArchiveBuildRoots::discover()` returns roots in this order. Each needs a `package.json` with `scripts.build`.

1. `packages/mksine`
2. Full archives only: each `resources/views/themes/{theme}`, alphabetically
3. Full archives only: each plugin under `config('mksine.plugins_path')` (default `plugins/`), alphabetically
4. The project root (its Vite build runs last)

When a root has no `node_modules/`, the command runs `npm ci` (with a `package-lock.json`) or `npm install` there first. Package theme assets under `packages/mksine/resources/views/themes/*/dist` are committed and not rebuilt.

## What ends up in the zip

`ReleaseArchiveBuildRoots::shouldIncludeInZip()` is the source of truth.

**Always excluded:**

- `.env` and `.env.*` (`.env.example` is kept)
- `node_modules/` and `.git/` anywhere
- Local data: `database/*.sqlite` (and journal files), `mksine-setup-database.sql`/`.sqlite`, `*.mksine-backup-*` files, `.DS_Store`, `Thumbs.db`
- `mksine-setup/` (the retired standalone installer)
- Any `uploads/` tree, including `public/uploads/`
- `storage/logs`, `storage/framework`, `storage/app` (only `.gitignore` files are kept, so the directories exist)
- `bootstrap/cache/*.php` (the command also deletes them before zipping)

**Core-only archives also exclude** `plugins/`, `resources/views/themes/` (except paths in `mksine.release_archive.include_theme_paths`), `packages/mksine/resources/views/themes/`, `public/themes/` and `public/plugins/`.

**`public/` is allowlisted:** `build/`, `vendor/mksine/`, `css/`, `js/`, `fonts/`, plus `themes/` and `plugins/` in full archives, and the root files `index.php`, `.htaccess`, `robots.txt`, `favicon*`. Everything else under `public/` is dropped.

Before zipping, package migrations are copied into `database/migrations/` so `migrate` on the server finds them.

## Production dependencies (`--no-dev`)

Without `--no-dev`, `vendor/` is zipped exactly as installed locally, including dev packages such as Pest and Faker. With `--no-dev`, the command:

1. copies the project, minus `vendor/` and everything the zip would exclude, to a temporary directory (the full `packages/mksine` source is copied because `composer install` links it into `vendor/`);
2. runs `composer install --no-dev --optimize-autoloader --no-scripts` there;
3. zips the staging copy and deletes it.

This needs Composer locally and network access (or a warm Composer cache). Without Faker, the installer's "sample content" step is skipped with a warning.

## Verifying an archive

```bash
unzip -l storage/app/mksine-release-*.zip | head -50
unzip -l storage/app/mksine-release-*.zip | grep -E '\.env(\s|$)|\.sqlite' && echo "STOP" || echo "ok"
```

For a split archive, the most reliable check is to install it the way a customer would:

```bash
unzip -q mksine-release.zip -d /tmp/site && cd /tmp/site/mksine
cp .env.example .env    # set DB_*, APP_URL
php artisan mksine:install --migrate --profile=shared --no-interaction
php -S 127.0.0.1:8000 -t ../public_html ../public_html/index.php
```

## Limitations

- **No checksum or signing.** Sign the zip externally if you need supply-chain guarantees.
- **No incremental builds and no npm version pinning.** Pin Node in CI.
- **Windows symlink behaviour is untested.**
- **Plugin `vendor/` folders and other files inside plugin sources ship as they are.** Keep plugin trees clean before building.

## See also

- [Deployment and hosting](deployment-hosting.md)
- [ZIP updater](zip-updater.md)
- Source: [`ReleaseArchiveCommand`](../../src/Console/Commands/ReleaseArchiveCommand.php), [`ReleaseArchiveBuildRoots`](../../src/Support/ReleaseArchiveBuildRoots.php), [`ReleaseArchiveOptions`](../../src/Support/ReleaseArchiveOptions.php), [`ReleaseArchiveZipper`](../../src/Support/ReleaseArchiveZipper.php), [`ReleaseArchiveStaging`](../../src/Support/ReleaseArchiveStaging.php)
