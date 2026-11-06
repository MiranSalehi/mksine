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
php artisan mks:release-build
```

A public release is **not** a zip of your own project. `mks:release-build` builds a clean application in a temporary folder and packs that:

1. `composer create-project laravel/laravel` (`--laravel`, default `^12.0`).
2. A copy of this package without docs, tests, build tooling or theme sources, installed as a path repository with `composer update --no-dev`.
3. `filament:install --panels` and the file steps of `mksine:install` (user model, `MksinePlugin` on the panel, config, migrations, translations, assets).
4. The plugins from `config('mksine.plugins_path')`, without `node_modules`, docs, tests, build files or stray archives, and their published assets.
5. Cleanup: READMEs, changelogs and doc folders are stripped from `vendor/`; `composer.json` points at `https://github.com/MiranSalehi/mksine` with `^{version}` so a server with Composer can update later; `composer.lock` is not shipped.

Only `app`, `bootstrap`, `config`, `database`, `lang`, `plugins`, `public`, `resources`, `routes`, `storage`, `vendor`, `artisan`, `composer.json` and `.env.example` are packed. Your project's own code, themes, `.env`, editor and AI-tool folders never reach a release. Every command in the build runs with a clean environment, so the maintainer's `APP_*` and `DB_*` settings do not leak in. It needs Composer and network access; `--skip-build` skips the npm builds of the package and plugins, and `--keep-staging` keeps the temporary application for inspection.

This writes to `storage/app/releases/{version}/`:

| File | Purpose |
| ---- | ------- |
| `mksine-{version}-shared-hosting.zip` | Split layout (`mksine/` + `public_html/`) for cPanel and DirectAdmin |
| `mksine-{version}-server.zip` | Single layout for a VPS; point the document root at `public/` |
| `mksine.php` | The quick installer (below), with the version stamped in |
| `SHA256SUMS` | Checksums; verify with `shasum -a 256 -c SHA256SUMS` |
| `release.json` | Version, release date, requirements, each zip's name, size and SHA-256, and the quick installer under `installer`, for the download page |

`scripts/publish-release.sh` (in the package) runs that build, verifies the checksums and attaches the files to the GitHub release for tag `v{version}`, which must already be pushed. It creates the release when it does not exist (`--draft` makes it a draft) and replaces the files when it does. It needs the GitHub CLI (`gh`) logged in. The download page reads the newest manifest from `https://github.com/MiranSalehi/mksine/releases/latest/download/release.json`.

```bash
git -C packages/mksine tag v1.23.0 && git -C packages/mksine push origin v1.23.0
packages/mksine/scripts/publish-release.sh
```

### Quick installer (`mksine.php`)

A single PHP file (source: [`resources/installer/mksine.php`](../../resources/installer/mksine.php)) that users upload to their web root and open at `/mksine.php`, instead of uploading the zip. It uses only PHP 7.0 syntax, so an old PHP version gets a readable message rather than a parse error. It:

1. asks for no code: the browser that clicks *Download and install* owns the run (a random secret in a `SameSite=Strict` cookie, its SHA-256 in the state file; step requests also need an `X-MKSine-Installer` header). Another browser can take over after 10 minutes without activity. Nothing is written before the run starts;
2. reads `release.json` from `releases/latest/download/` and downloads the zip for its layout from the **versioned** URL `releases/download/v{version}/`, in time-limited parts resumed with HTTP `Range`;
3. checks the SHA-256 from the manifest, then checks every entry (no `..` or absolute paths, no existing file overwritten) before extracting in batches;
4. writes `index.php` and the merged `.htaccess` last, sets `MKSINE_PUBLIC_PATH` in `.env.example` to the real web folder, and deletes the zip, its state file and itself;
5. posts a one-time code to `/install/handoff` ([`InstallerHandoff`](../../src/Core/Install/Web/InstallerHandoff.php): SHA-256 in `storage/mksine/installer-handoff.json`, valid for 30 minutes, deleted on use), so `/install` opens at *Requirements* for the same browser. Releases before 1.23.0 have no handoff route; the installer then opens `/install`.

Layout: shared (`../mksine` + the web root) unless the web root is a folder named `public` whose parent holds nothing else, in which case the server zip is extracted into the parent. A zip named `mksine-{version}-{shared-hosting|server}.zip` next to the file is used instead of downloading; it is checksum-verified only when it matches the latest manifest. `MKSINE_INSTALLER_MANIFEST_URL` (environment) overrides the manifest URL for testing.

`mks:release-archive` (below) still zips **your own project**, for deploying your own site.

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
