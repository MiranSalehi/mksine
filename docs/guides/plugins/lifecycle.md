---
title: Plugin lifecycle
description: Exact semantics of install, activate, deactivate, uninstall, and boot — including the boot guard.
order: 11
---

# Plugin lifecycle

Plugins move through five states managed by [`PluginLifecycle`](../../../src/Core/Plugins/PluginLifecycle.php) and surfaced through [`PluginManager`](../../../src/Core/Plugins/PluginManager.php). This page documents every transition, the contract method that runs, and the database row in `mks_plugins` that backs it.

## State diagram

```
              not_installed
                   │ install / mks-plugin:install
                   ▼
               installed ──────┐
                   │           │ uninstall (with --delete-data optional)
              activate         │
                   ▼           ▼
              ┌──active──→ inactive ─────→ not_installed
              │   │ boot()                  (mks-plugins row deleted)
boot fails ───┘   │ on every request
              boot_failed (auto via PluginBootGuard)
```

`mks_plugins.status` mirrors the state. `boot_failed = true` is an auxiliary flag set independently when the boot guard detects a stale flag (see below).

## `install()`

Triggered by `mks-plugin:install` or `PluginManager::install($id)`.

What happens, in order ([`PluginLifecycle::install()`](../../../src/Core/Plugins/PluginLifecycle.php)):

1. Insert `mks_plugins` row with `status = installed`.
2. Update the in-process registry.
3. Instantiate the plugin class via [`PluginInterface`](../../reference/contracts.md#plugininterface).
4. Run pending migrations under `database/migrations/` via `Artisan::call('migrate')`.
5. Call `PluginInterface::install()`.

Failure handling: any exception rolls back the `mks_plugins` row and rethrows. Implementations of `install()` **must be idempotent** — Laravel migrations dedupe on `migrations` table, but your custom seed/install code should also tolerate re-runs.

Use it for:

- Initial data seeding that is **not** a migration (e.g. default settings rows).
- Filesystem setup the plugin needs before activation (e.g. local storage subdirectories).

Do **not** register routes or hooks here — those belong in `boot()`.

## `activate()`

Triggered by `mks-plugin:activate` or `PluginManager::activate($id)`. Auto-installs if the plugin is not yet installed.

What happens:

1. Update `mks_plugins.status = active`, set `activated_at = now()`, clear `deactivated_at`.
2. Instantiate the plugin and call `PluginInterface::activate()`.
3. Publish translations (`PluginManifest::publishTranslations()`) — overwrites `lang/vendor/{id}/`.
4. The CLI also publishes assets via `PluginManifest::publishAssets()` (only `mks-plugin:activate`, not `PluginManager::activate()` directly).

If the plugin previously had `boot_failed = true`, the manager clears it before activating.

Use `activate()` for:

- Database flags or feature toggles that should switch on whenever the plugin is active.
- One-off "first activation" seeding when paired with a guard column (e.g. set `seeded_at` after first run).

Routes, container bindings, hook subscriptions, etc., still belong in `boot()` — `activate()` runs **once** per state transition, while `boot()` runs every request.

## `boot()`

Called on **every request** (and Artisan run) once the plugin is active, by [`PluginManager::bootPlugin()`](../../../src/Core/Plugins/PluginManager.php). Order:

1. The boot guard writes `storage/framework/cache/mks_plugins/{id}.booting.json` (atomic write).
2. The plugin class is instantiated via the container.
3. `PluginInterface::boot()` runs.
4. `routes/web.php` is loaded under the `web` middleware group; `routes/api.php` under `api` (prefixed with `/api`).
5. The boot guard clears the flag.

If `boot()` throws, the guard writes `mks_plugins.boot_failed = 1`, sets `boot_error`, and `boot_failed_at`. The plugin is **disabled until you reactivate it** via `mks-plugin:activate`.

### Boot guard semantics

[`PluginBootGuard`](../../../src/Core/Plugins/PluginBootGuard.php) implements the "Per-Plugin Flag + TTL + Stale Detection" strategy:

- TTL is `mksine.plugins.boot_guard_ttl` (default **15 seconds**).
- A booting flag younger than TTL is considered "still booting" — the next request leaves it alone (concurrency-safe).
- Only flags **older than** TTL trigger auto-disable. This avoids flapping under traffic.
- Corrupt JSON deletes the file but does **not** disable the plugin.

If you legitimately need long boots (e.g. cache warmup), increase `MKS_CMS_PLUGIN_BOOT_GUARD_TTL` accordingly. Do not set it lower than your slowest realistic boot, or you will start flagging healthy plugins as failed.

## `deactivate()`

Triggered by `mks-plugin:deactivate` or `PluginManager::deactivate($id)`.

What happens:

1. Resolve the plugin instance (cached or freshly instantiated).
2. Call `PluginInterface::deactivate()`.
3. Update `mks_plugins.status = inactive`, set `deactivated_at = now()`.
4. Refresh the registry.

`deactivate()` must **never delete data**. The contract is "stop the plugin from being active" — published assets, database tables, and language files remain on disk. Use it for:

- Disabling cron schedules the plugin owns.
- Tearing down third-party connections (e.g. closing webhooks the plugin opened).

If the plugin is currently active and you call `uninstall()`, deactivation runs first automatically.

## `uninstall()`

Triggered by `mks-plugin:uninstall` (with optional `--delete-data` and confirmations) or `PluginManager::uninstall($id, $deleteData)`.

What happens:

1. If active, deactivate first (see above).
2. Instantiate the plugin and call `PluginInterface::uninstall($deleteData)`.
3. Delete the `mks_plugins` row.
4. Refresh the registry.

`$deleteData` is intentionally a hint, not a contract — the package does not auto-drop your tables. Implementations should:

- When `$deleteData === true`: drop tables created by the plugin migrations (e.g. via `Schema::dropIfExists()`), wipe storage directories, remove published assets via `PluginManifest::removePublishedAssets()`.
- When `$deleteData === false`: keep tables and files. Re-installing later should be a no-op for data.

Migrations are **not** rolled back automatically. Provide explicit `down()` methods or do the drops in `uninstall(true)` — your call.

## What runs when (cheat sheet)

| Action | DB row | Migrations | `install()` | `activate()` | `boot()` | `deactivate()` | `uninstall()` |
|--------|--------|-----------|------------|------------|---------|---------------|---------------|
| `mks-plugin:install` | inserted | run | yes | – | – | – | – |
| `mks-plugin:activate` | updated | – | (auto if needed) | yes | next request | – | – |
| every request when active | – | – | – | – | yes | – | – |
| `mks-plugin:deactivate` | updated | – | – | – | – | yes | – |
| `mks-plugin:uninstall` | deleted | – | – | – | – | (if active) | yes |
| `mks-plugin:migrate` | – | run | – | – | – | – | – |

## Common pitfalls

- **Holding state in a singleton:** `PluginInterface` instances are not preserved across requests. Use the Laravel container or a dedicated table.
- **Throwing in `boot()` for "developer-only" issues:** every visitor pays the boot cost. Guard with `if (! config('app.debug')) return;` for development noise — or better, fail loudly in tests.
- **Doing route registration in `register()`-style hooks:** there is no `register()` lifecycle; everything goes through `boot()`.
- **Assuming `migrationsPath()` will be re-scanned automatically after a deploy:** it is, but **only** if `mks-plugin:migrate` is part of your deploy script. Add it.

## See also

- [Commands reference: plugin commands](../../reference/commands.md#plugin-commands)
- [Boot guard troubleshooting](../../operations/troubleshooting.md#plugin-boot-failed)
- [ADR: committed plugin assets](../../adr/002-committed-plugin-assets.md)
