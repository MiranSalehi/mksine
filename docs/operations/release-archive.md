---
title: Release archive (mks:release-archive)
---

# Release archive (`mks:release-archive`)

`mks:release-archive` produces a deployable `.zip` of the entire project — code, vendored dependencies, compiled assets — minus the things you don't ship to production. It exists because shared hosting, DirectAdmin and cPanel customers can't run `composer install` or `npm run build` on the server, and Git deploy isn't always an option. It is **not** a replacement for proper CI/CD.

This page documents what the command does, why it makes the choices it makes, and what to verify before relying on it for production deploys.

## Command surface

```
php artisan mks:release-archive
    [--output=PATH]      # Absolute or project-relative path for the .zip
    [--skip-build]       # Skip npm run build; only zip
    [--dry-run]          # Print build roots and output path; do nothing else
```

| Behaviour                | What happens                                                                                       |
| ------------------------ | -------------------------------------------------------------------------------------------------- |
| Default                  | Discover build roots, run `npm run build` in each, then create `storage/app/mksine-release-{ts}.zip` |
| `--output=relative/path` | Resolved relative to the project root                                                              |
| `--output=/abs/path.zip` | Used verbatim                                                                                      |
| `--skip-build`           | No `npm` invocation; existing `public/build/`, `public/themes/`, etc. are zipped as-is             |
| `--dry-run`              | Lists build roots in execution order and the output path; does **not** delete or write anything   |

If no build roots are found and you didn't pass `--skip-build`, the command **fails** instead of producing an under-built archive. That's intentional — silent partial builds caused production incidents in earlier iterations.

## Build root discovery

Order matters: `ReleaseArchiveBuildRoots::discover()` returns roots in the sequence below, and the command runs `npm run build` in each one before creating the zip. Each root must have a `package.json` with a `scripts.build` entry; otherwise it is skipped silently.

1. `packages/mksine/resources/views/themes/mksine` — the package’s default theme.
2. `packages/mksine` — the package itself.
3. `resources/views/themes/{theme}` — every project-level theme that has a `package.json` with `scripts.build` (alphabetical order, the package's default theme is deduplicated).
4. `plugins/{plugin_id}` — top-level plugin directories with `package.json` and `scripts.build` (alphabetical).
5. `package.json` at the project root, if it has a `scripts.build`.

> Note the path on point 4: discovery is hardcoded to `plugins/`, **not** `config('mksine.plugins_path')`. If your plugins live elsewhere, builds for those plugins will silently be skipped. This is a known limitation; see [Honest limitations](#honest-limitations).

If you need a different order or extra roots, fork the command. There is no extension hook.

## What ends up in the zip

`ReleaseArchiveBuildRoots::shouldIncludeInZip()` is the source of truth. The list below paraphrases it.

**Always excluded:**

- `node_modules/` (anywhere in the tree).
- `.git/` (anywhere).
- `mksine-setup/` (the post-install bootstrap directory).
- `.env` and `.env.*` (anything except `.env.example`, which is included).

**`public/` is allowlisted, not blocklisted.** Inside `public/`, only the following may be packed:

| Path under `public/`               | Why allowed                                                                |
| ---------------------------------- | -------------------------------------------------------------------------- |
| `build/`                           | Vite’s compiled bundle.                                                    |
| `themes/`                          | Published theme assets (`mks:theme-publish`).                              |
| `vendor/mksine/`                   | Symlinks/published assets from the package itself.                          |
| `css/`, `js/`, `fonts/`            | Conventional asset roots if your app uses them.                             |
| `plugins/`                         | Published plugin assets (each plugin's `dist/` copied to `public/plugins/{id}/`). |
| `index.php`, `.htaccess`           | Apache/PHP front controller.                                                |
| `robots.txt`, `favicon*`           | Common root-level static files.                                             |

Everything else in `public/` is **dropped**. If you store user uploads in `public/uploads/`, they will not be included in the archive — that is by design (uploads belong on a persistent volume, not in your code release). Migrate your media to `storage/app/public` and use `php artisan storage:link` on the server.

**Everything outside `public/`** that is not in the always-excluded list is included. That means `vendor/`, `storage/` (including local logs, sessions, and cached views — review and clear before building if needed), `bootstrap/cache/`, plugin source, theme source, and so on.

## How vendor/ symlinks are handled

`composer.json` uses a `path` repository to map `miran/mksine` to `packages/mksine`. Locally that creates a symlink at `vendor/miran/mksine -> packages/mksine`. The zipper keeps **the symlink path** (`vendor/miran/mksine/...`) rather than resolving it back to the real path. This is critical: Composer's autoloader is configured for the symlink path, and rewriting it would break class resolution on the deployed server.

If you build on a machine that resolved symlinks differently (e.g. WSL with weird mount semantics), verify with `unzip -l` that paths under `vendor/miran/mksine/` exist in the archive.

## Output path resolution

```php
ReleaseArchiveZipper::resolveOutputPath($basePath, $outputOption);
```

- `null` or empty → `storage/app/mksine-release-YYYY-MM-DD-HHMMSS.zip`.
- Absolute (`/...`, `C:\...`, `\\share\...`) → used verbatim.
- Relative → joined with the project root.

If the zip lives **inside the project tree**, the zipper computes its relative path and excludes it from being packed into itself. Don't write the output to `public/` — the zipper would then try to pack it (since `public/` is allowlisted) and you could end up with a recursive infinite-zip situation; the path-skip prevents that, but you'll still ship the previous archive in the new one. Use `storage/app/` or a path outside the project entirely.

## What to do before shipping

1. **Clean caches.** The zipper packs `bootstrap/cache/*`. Run `php artisan optimize:clear` (or at least `view:clear`, `route:clear`, `config:clear`) so the deployed app re-builds caches against the deployed environment.
2. **Verify `.env.example`** matches the keys your application reads in production. The deployed server will copy it to `.env` and fill in secrets.
3. **Double-check `storage/`**. Logs (`storage/logs/laravel.log`), failed jobs, and cached sessions get packed if present. Either rotate them out or delete them before running the command.
4. **Confirm plugin and theme assets are published** (`php artisan mks-plugin:publish` and `mks:theme-publish` for each). The build phase compiles, but does not publish — `public/plugins/{id}/` and `public/themes/{id}/` are populated by `publish` commands.
5. **Test the archive** on a staging server matching the production OS, PHP version, and web server. The first time you trust this command in production is too late.

## What the deployed server still needs

The archive is **not** plug-and-play to the extent that:

- `php artisan key:generate` (or copy `APP_KEY` from your secrets store) on first deploy.
- `php artisan migrate` against the production DB.
- `php artisan storage:link`.
- Web server configuration (DocumentRoot pointing at `public/`, `mod_rewrite` / `try_files`, etc.).
- A `.env` file with real secrets (the archive intentionally omits it).
- Native dependencies for image optimization, if you enabled them.

These are documented in [Deployment and hosting](deployment-hosting.md).

## Honest limitations

- **Hardcoded `plugins/` path.** Discovery doesn’t honour `config('mksine.plugins_path')`. If you customise the plugin directory location, plugin builds are silently skipped. Either keep plugins at `plugins/` or build them manually before running the archive with `--skip-build`.
- **No incremental builds.** Every run is a full build; no caching across runs.
- **No npm version pinning.** The command runs whatever `npm` is on `$PATH`. Use a Node version manager and pin it in CI.
- **No checksum or signing.** The archive is just a zip. If you care about supply-chain integrity, sign it externally and verify on the server.
- **No exclusion of `storage/framework/sessions`, `cache`, or `views`.** They get packed if present. Clear them first.
- **Symlink semantics on Windows are untested.** The zipper assumes POSIX symlink behaviour. On Windows, results may vary.
- **`public/build/.vite/manifest.json`** is included (under `build/`). Make sure your Vite manifest is up to date — a stale manifest plus new compiled bundles is a confusing class of bug.
- **Failed builds leave you with no zip and a noisy console.** The command fails fast; nothing is half-written.

## Verifying the archive

Before trusting an archive in production:

```bash
# What's inside?
unzip -l storage/app/mksine-release-*.zip | head -50

# Spot-check the public allowlist:
unzip -l storage/app/mksine-release-*.zip | grep ' public/' | head -20

# Confirm plugin builds are present:
unzip -l storage/app/mksine-release-*.zip | grep ' plugins/' | head -20

# Make sure .env didn't sneak in:
unzip -l storage/app/mksine-release-*.zip | grep -E '\.env(\s|$)' && echo "STOP" || echo "ok"
```

## When not to use it

- If you have CI/CD with rsync, Deployer, or Forge: use those instead. They handle migrations, atomic releases, and rollback.
- If your hosting allows `composer install` over SSH: skip the zip entirely.
- If your project has artifacts the allowlist doesn't anticipate (PWA service workers in `public/sw.js`, custom manifest files, etc.): patch `ReleaseArchiveBuildRoots::isPublicPathAllowed()` first or extend the allowlist via a fork.

`mks:release-archive` is a pragmatic tool for a constrained deployment story. Treat it accordingly.

## See also

- [`reference/commands.md`](../reference/commands.md) — quick reference for all package commands.
- [`operations/deployment-hosting.md`](deployment-hosting.md) — server-side configuration and post-deploy steps.
- [`operations/troubleshooting.md`](troubleshooting.md) — diagnosing problems in deployed installations.
- Source: [`Miran\Mksine\Console\Commands\ReleaseArchiveCommand`](../../src/Console/Commands/ReleaseArchiveCommand.php), [`Miran\Mksine\Support\ReleaseArchiveBuildRoots`](../../src/Support/ReleaseArchiveBuildRoots.php), [`Miran\Mksine\Support\ReleaseArchiveZipper`](../../src/Support/ReleaseArchiveZipper.php).
