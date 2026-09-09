---
title: ZIP updater
description: Update project plugins and themes from ZIP files. Core miran/mksine is updated with Composer, not ZIP.
order: 30
---

# ZIP updater

ZIP uploads replace **project plugins** and **project themes** on a running server that has no npm (and often no Composer for those trees). The core `miran/mksine` package is **not** updated from a ZIP. Swapping `vendor/` or `packages/mksine` would desync `composer.lock` and the autoloader.

| Target | Filament UI | CLI | Applies to |
|--------|-------------|-----|------------|
| Plugin | **Plugin management → Update a Plugin** | `php artisan mks-plugin:update {id} {zip}` | Project plugins only (`plugins/{id}`) |
| Theme | **Themes → Update a Theme** | `php artisan mks:theme-update {id} {zip}` | Project themes only (`resources/views/themes/{id}`) |
| Core | **System update** playbook | `php artisan mksine:update` | Composer: `composer update miran/mksine` |

Composer-installed plugins/themes and package-theme installs are **rejected** by the ZIP pipeline. Update those with Composer on a machine that has Composer, then deploy.

`php artisan mks:release-archive` builds a **full application** deploy ZIP (lockfile + `vendor/` + compiled assets). It is not a core-only archive and must not be treated as a `miran/mksine` drop-in. See [Release archive](release-archive.md).

## Core: Composer, not ZIP

Source of truth is `composer.lock`. On a host with Composer:

```bash
composer update miran/mksine
php artisan vendor:publish --tag=mksine-migrations
php artisan migrate --force
```

Or: `php artisan mksine:update --force` (interactive confirm unless `--force`; non-interactive runs require `--force`). If Composer is missing, the command fails and prints an offline playbook.

On a host **without** Composer: run `composer update miran/mksine` on a build machine, then deploy at least `composer.json`, `composer.lock`, and `vendor/`. Path-repository installs must update `packages/mksine` first — `composer update` does not git-pull that path.

The **System update** page (Super Admin, `updater.enabled`) shows both playbooks and a link to Console Terminal when Composer is on PATH. Prefer SSH for long Composer runs (HTTP timeouts).

## What the ZIP pipeline guarantees

Plugin and theme runs — UI or CLI — share the same envelope:

- **Single writer**: a per-target `flock()` lock prevents two operators from updating the same target simultaneously. Different targets may run in parallel.
- **Atomic swap**: extraction happens in a staging dir on the same filesystem as the target, followed by two `rename()` syscalls: `target → backup`, then `staging → target`.
- **Safe extract**: entries are written one-by-one (never `ZipArchive::extractTo`). Symlinks, path traversal, uncompressed-size caps, and entry-count caps reject the archive before swap.
- **Backup retention**: every successful swap archives the previous tree under `{target-parent}/.mks-backups/{id}-{TS}[-v{ver}]`. The oldest are pruned beyond `config('mksine.updater.keep_backups')` (default **3**).
- **Per-run log**: `storage/logs/mksine-updates/{target}-{id}-{TS}.log` captures every step, warning, and error.
- **Browser-tab safety**: `set_time_limit(0)` + `ignore_user_abort(true)` stop a closed tab from corrupting a swap.
- **Publish-first, migrate-last** (plugins): assets and translations land before migrations. A non-zero Artisan exit code fails the step. Migration failures are reported loudly but **do not** delete the new code — see [Failure modes](#failure-modes).

## Who can use it

**Filament UI** (plugin/theme ZIP and System update): Shield **Super Admin** (`config('filament-shield.super_admin.name')`, default `super_admin`) and `config('mksine.updater.enabled')`.

**CLI**: SSH (or any user who can run Artisan). There is no Super Admin gate on the CLI. `mksine.updater.enabled` still applies to update and rollback commands.

## ZIP layout requirements

### Plugin ZIP

Root contains a `plugin.php` manifest or a single top-level folder whose manifest file is at the root of that folder. The manifest `id` must **exactly match** the plugin being updated. Example:

```text
my-plugin.zip
└── my-plugin/
    ├── plugin.php          ← id: my-plugin, version: 1.3.0
    ├── src/…
    ├── resources/dist/…    (pre-built assets; see below)
    ├── resources/lang/…
    └── database/migrations/…
```

Assets **must be pre-built**; the server has no npm. Commit `dist/` into the ZIP.

If the ZIP includes `composer.json` with `require` entries other than `php` / `ext-*`, it must also include `vendor/`. Production cannot run Composer for the plugin.

### Theme ZIP

Root contains `theme.json`. Identity is accepted if any of these match the target identifier:

- `theme.json.identifier`
- slug of `theme.json.name`
- wrapping folder name
- wrapping folder `{id}-*` or `{id}_*` (GitHub-style `voltech-1.2.0/`)

A flat ZIP (files at archive root, no wrapper folder) is accepted when identifier or name matches. A `dist/` directory is **required**.

## Version rules

- `new > current`: accepted.
- `new == current`: rejected unless `--force` on the CLI, the Filament **Force** toggle, or `mksine.updater.allow_same_version_reinstall`.
- `new < current`: rejected unless `--force` / Force toggle.

The Filament plugin/theme update modal includes Force for recovery. Prefer a higher version in normal releases.

## Post-update steps

### Plugin

1. `mks-plugin:discover` (rebuild discovery cache)
2. `mks-plugin:publish-lang {plugin}`
3. `mks-plugin:publish {id} --force`
4. `optimize:clear`
5. `mks-plugin:migrate {id}` (last; non-zero exit fails the run)
6. DB row set to `installed` (not `active`) — see [Activation lifecycle](#activation-lifecycle).

### Theme

1. Clear theme cache.
2. `publishAssets($id)` (copies `dist/` → `public/themes/{id}/`).
3. `mks:theme-publish-lang {theme}` (positional argument).
4. `optimize:clear`.
5. If required plugins are inactive or missing, the run **succeeds** with a warning (it does not fail).

## Activation lifecycle

Plugins that were **active** before the update are demoted in the **database only** (`installed`). The plugin `deactivate()` hook is **not** invoked (it may tear down gateways or data). If the swap fails, an active row is restored. After a successful swap the operator must click **Activate** (or `php artisan mks-plugin:activate {id}`) **in a subsequent request** so a fresh PHP worker loads the new code.

Themes stay active: views render from disk.

## Failure modes

| Phase | What happened | What's on disk | What's in DB | Operator action |
|-------|---------------|----------------|--------------|-----------------|
| Validation | ZIP rejected before touching disk | unchanged | unchanged | Fix ZIP |
| Replace | Swap failed mid-rename | AtomicReplacer rolled back | plugin status restored if it was active | Retry or inspect log |
| Post | Swap committed, publish failed | new code live | unchanged (publishing doesn't touch DB) | Run the failing publish command manually |
| Post (migrate) | Migration failed | **new code live** | **may be partially migrated** | **Manual inspection required**. Plugin is marked `boot_failed=true` and set to `inactive`. See log. Decide: roll forward (fix and re-run `mks-plugin:migrate`), or roll back (`mks-plugin:rollback` + restore DB snapshot). |

The updater **never auto-reverses migrations**. Forward migrations are expected to be backward-compatible; destructive changes are the operator's responsibility.

## Rollback

Plugin and theme rollback restores the most recent backup. **Code only** — migrations are not reversed. Filament shows a rollback button (Super Admin + updater enabled) with a confirm dialog. CLI:

```bash
php artisan mks-plugin:rollback my-plugin
php artisan mks:theme-rollback my-theme
```

Interactive CLI asks for confirm; `--no-interaction` skips the prompt. Combine with a database snapshot restore if you need full state restoration.

There is no `mksine:rollback` for the core package.

## Observability

- **Log file**: `storage/logs/mksine-updates/{target}-{id}-{TS}.log`. One file per run.
- **UI**: Filament notifications after plugin/theme update or rollback.
- **Backups**: `{target-parent}/.mks-backups/` — verifiable by operators, never hidden.

## Configuration

See [Configuration → updater](../reference/configuration.md#updater).

Key toggles:

- `mksine.updater.enabled` (env: `MKS_CMS_UPDATER_ENABLED`, default `true`).
- `mksine.updater.keep_backups` (env: `MKS_CMS_UPDATER_KEEP_BACKUPS`, default `3`).
- `mksine.updater.max_zip_size_mb` (env: `MKS_CMS_UPDATER_MAX_ZIP_MB`).
- `mksine.updater.max_uncompressed_mb` (env: `MKS_CMS_UPDATER_MAX_UNCOMPRESSED_MB`, default `1024`).
- `mksine.updater.max_zip_entries` (env: `MKS_CMS_UPDATER_MAX_ZIP_ENTRIES`, default `10000`).
- `mksine.updater.allow_same_version_reinstall` (env: `MKS_CMS_UPDATER_ALLOW_REINSTALL`, default `false`).

## Related

- [Commands → updater commands](../reference/commands.md#updater-commands)
- [Release archive](release-archive.md)
- [Upgrade guide](../meta/upgrade-guide.md)
