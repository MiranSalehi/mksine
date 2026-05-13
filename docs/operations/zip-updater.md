---
title: ZIP updater
description: Update plugins, themes, and the core miran/mksine package from ZIP files when the production server has no composer or npm access.
order: 30
---

# ZIP updater

The ZIP updater lets a **Super Admin** replace a project plugin, project theme, or the core `miran/mksine` package on a running server by uploading a ZIP file. It is the only supported on-server update path when the host has **no composer and no npm** available.

Three independent flows — all orchestrated by the same pipeline:

| Target | Filament UI | CLI | Applies to |
|--------|-------------|-----|------------|
| Plugin | **Plugin management → Update a Plugin** | `php artisan mks-plugin:update {id} {zip}` | Project plugins only (`plugins/{id}`) |
| Theme | **Themes → Update a Theme** | `php artisan mks:theme-update {id} {zip}` | Project themes only (`resources/views/themes/{id}`) |
| Core | **System update** page | `php artisan mksine:update {zip}` | Path-repo installs (`packages/mksine/`) |

Composer-installed plugins/themes and package-theme installs are **explicitly rejected**: there is no way to update them on a server without composer.

## What the pipeline guarantees

Every update run — UI or CLI — passes through the same envelope:

- **Single writer**: a per-target `flock()` lock prevents two admins from updating the same target simultaneously. Different targets may run in parallel.
- **Atomic swap**: extraction happens in a staging dir on the same filesystem as the target, followed by two `rename()` syscalls: `target → backup`, then `staging → target`.
- **Backup retention**: every successful swap archives the previous tree under `{target-parent}/.mks-backups/{id}-{TS}[-v{ver}]`. The oldest are pruned beyond `config('mksine.updater.keep_backups')` (default **3**).
- **Per-run log**: `storage/logs/mksine-updates/{target}-{id}-{TS}.log` captures every step, warning, and error.
- **Browser-tab safety**: `set_time_limit(0)` + `ignore_user_abort(true)` stop a closed tab from corrupting a swap.
- **Publish-first, migrate-last**: assets and translations land before migrations. Migration failures are reported loudly but **do not** delete the new code — see [Failure modes](#failure-modes).

## Who can use it

Both the UI and CLI require the caller to hold the Shield **Super Admin** role (`config('filament-shield.super_admin.name')`, default `super_admin`). The UI actions are hidden for non-super-admins; the CLI enforces the same gate.

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

### Theme ZIP

Root contains a `theme.json` or a single top-level folder named `{identifier}/` with `theme.json` inside. The ZIP's identifier (root folder name or slugified `theme.json.name`) must match the target. A `dist/` directory is **required** — the updater rejects ZIPs without it.

### Core ZIP

Root contains `composer.json` (with `"name": "miran/mksine"`) and `config/mksine.php` (for the version). The ZIP's `require` and `require-dev` maps must be **byte-identical** to the currently installed core's. Any added / removed / retightened dependency rejects the update with a clear diff message.

Use `php artisan mks:release-archive` on your build machine to generate a correct core ZIP.

## Version rules

- `new > current`: accepted.
- `new == current`: rejected unless `--force` on the CLI.
- `new < current`: rejected unless `--force` on the CLI.
- The UI never offers downgrades — you must use the CLI with `--force`.

## Post-update steps

Each target runs a tailored checklist after the swap:

### Plugin
1. `mks-plugin:discover --clear` (rebuild discovery cache)
2. `mks-plugin:publish-lang`
3. `mks-plugin:publish {id} --force`
4. `optimize:clear`
5. `mks-plugin:migrate {id}` (last)
6. DB row set to `installed` (not `active`) — see [Activation lifecycle](#activation-lifecycle).

### Theme
1. Clear theme cache.
2. `publishAssets($id)` (copies `dist/` → `public/themes/{id}/`).
3. `mks:theme-publish-lang --theme={id}`.
4. `optimize:clear`.

### Core
1. `vendor:publish --tag=mksine-migrations --force`
2. `vendor:publish --tag=mksine-lang --force`
3. `vendor:publish --tag=mksine-fonts --force`
4. `optimize:clear`
5. `migrate --force` (last)

## Activation lifecycle

Plugins that were **active** before the update are deactivated during the swap, then left in `installed` status after a successful swap. An operator must click **Activate** from the Plugins page (or run `php artisan mks-plugin:activate {id}`) **in a subsequent request**. This guarantees a fresh PHP worker with a clean autoloader picks up the new code.

Themes behave differently: the active theme stays active because views render lazily from disk — no class autoloading is involved.

## Failure modes

| Phase | What happened | What's on disk | What's in DB | Operator action |
|-------|---------------|----------------|--------------|-----------------|
| Validation | ZIP rejected before touching disk | unchanged | unchanged | Fix ZIP |
| Replace | Swap failed mid-rename | AtomicReplacer rolled back | unchanged | Retry or inspect log |
| Post | Swap committed, publish failed | new code live | unchanged (publishing doesn't touch DB) | Run the failing publish command manually |
| Post (migrate) | Migration failed | **new code live** | **may be partially migrated** | **Manual inspection required**. Plugin is marked `boot_failed=true` and set to `inactive`. See log. Decide: roll forward (fix and re-run `mks-plugin:migrate`), or roll back (`mks-plugin:rollback` + restore DB snapshot). |

The updater **never auto-reverses migrations**. Forward migrations are expected to be backward-compatible; destructive changes are the operator's responsibility.

## Rollback

Each target has a rollback CLI command that restores the most recent backup and demotes the plugin back to `installed` status:

```bash
php artisan mks-plugin:rollback my-plugin
php artisan mks:theme-rollback my-theme
php artisan mksine:rollback
```

Rollback restores **CODE ONLY**. Migrations applied since the backup are **not** reversed. Combine with a database snapshot restore if you need full state restoration.

## Why core updates prefer CLI over UI

Core updates replace the very code currently executing. The in-process UI path works because:

- PHP does not reload already-loaded classes mid-request — existing classes remain usable.
- The PSR-4 prefix `Miran\Mksine\\` still resolves to the same path after the swap; new classes load from the new files.
- `CoreUpdater::preloadPostSwapClasses()` forces-load the kernel classes we need after the swap.

But none of that beats running the update from a fresh PHP-FPM process that boots, performs the swap, and exits. When SSH access is available, prefer:

```bash
php artisan mksine:update /path/to/mksine-core.zip
```

## Observability

- **Log file**: `storage/logs/mksine-updates/{target}-{id}-{TS}.log`. One file per run, appended line-by-line.
- **UI result panel** (`SystemUpdate`): shows the last run's steps, warnings, error, and log path.
- **Backups**: `{target-parent}/.mks-backups/` — verifiable by operators, never hidden.

## Configuration

See [Configuration → updater](../reference/configuration.md#updater).

Key toggles:

- `mksine.updater.enabled` (env: `MKS_CMS_UPDATER_ENABLED`, default `true`).
- `mksine.updater.keep_backups` (env: `MKS_CMS_UPDATER_KEEP_BACKUPS`, default `3`).
- `mksine.updater.max_zip_size_mb` (env: `MKS_CMS_UPDATER_MAX_ZIP_MB`, default `256`).
- `mksine.updater.allow_same_version_reinstall` (default `false`).

## Related

- [Commands → updater commands](../reference/commands.md#updater-commands)
- [Release archive](release-archive.md)
- [Upgrade guide](../meta/upgrade-guide.md)
