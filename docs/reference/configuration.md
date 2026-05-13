---
title: Configuration
description: Every config key in config/mksine.php, with default, env override, type, and consumer.
order: 5
---

# Configuration

This page is the canonical reference for `config/mksine.php`. The version of the file in your application **may** drift if you skipped a release; compare against [the package source](../../config/mksine.php) when in doubt.

> **Where it lives.** After `php artisan mksine:install`, the file is published to `config/mksine.php`. The package fallback (used until you publish) lives at `packages/mksine/config/mksine.php`.

## Top-level shape

```php
return [
    'version' => '1.0.0',
    'features' => [...],
    'cache' => [...],
    'user_model' => App\Models\User::class,
    'sync_auth_user_model' => true,
    'plugins_path' => 'plugins',
    'plugins' => ['boot_guard_ttl' => 15],
    'media' => [...],
    'content' => [...],
    'hooks' => [...],
    'security' => [...],
    'country_dial_codes' => [...],
    'api' => [...],   // reserved
];
```

## `version`

| Default | Type |
|---------|------|
| `'1.0.0'` | string |

Read by [`Mksine::version()`](facades-and-managers.md#mksine-facade) and `mksine:info`. Treat it as **informational only** — change it through composer/git, not by editing this key.

## `features`

Boolean toggles consumed by [`Mksine::isFeatureEnabled()`](facades-and-managers.md#mksine-facade) and several internal panel registrations.

| Key | Env | Default | Effect when `false` |
|-----|-----|---------|---------------------|
| `content_management` | `MKS_CMS_CONTENT_MANAGEMENT` | `true` | Posts and Categories resources are not registered. |
| `media_management` | `MKS_CMS_MEDIA_MANAGEMENT` | `true` | Media library resource is not registered. |
| `plugin_system` | `MKS_CMS_PLUGIN_SYSTEM` | `true` | Plugin discovery, lifecycle, and Plugins resource are disabled. |
| `theme_management` | `MKS_CMS_THEME_MANAGEMENT` | `false` | Theme picker / Theme resource not exposed. |
| `page_builder` | `MKS_CMS_PAGE_BUILDER` | `false` | Page type `builder` and `PageBuilderField` are unavailable. |

Disabling a feature does **not** remove the underlying tables or data — it only skips Filament registration and route mounting. Re-enable to restore.

## `cache`

Internal cache settings used for hook discovery, plugin registry, etc. They do **not** override Laravel’s top-level `config('cache.*')`.

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `enabled` | `MKS_CMS_CACHE_ENABLED` | `true` | Master switch for MKSine internal caches. |
| `prefix` | `MKS_CMS_CACHE_PREFIX` | `'mks_cms'` | Prefix added to internal cache keys. |
| `ttl` | `MKS_CMS_CACHE_TTL` | `3600` | Default TTL in seconds. Individual managers may override (themes: 3600s; hook discovery: see `hooks.cache_discovery`). |
| `driver` | `MKS_CMS_CACHE_DRIVER` | `null` | When `null`, Laravel’s default cache driver is used. Set to a store name from `config('cache.stores')` to isolate. |

Use a non-default driver only when you actually need cache isolation — otherwise you create operational surprise (one more cache to flush during deploys).

## `user_model`

| Default | Env | Type |
|---------|-----|------|
| `App\Models\User::class` | `MKS_CMS_USER_MODEL` | class-string |

The Eloquent class used by:

- The package’s author/owner relationships.
- Filament panel auth (when `sync_auth_user_model` is `true`, see below).
- Filament Shield’s `auth_provider_model` (same condition).

The class **must** implement [`MksUserInterface`](contracts.md#mksuserinterface) (or extend a class that does). Plugins should subclass instead of editing this — see [`guides/auth/user-subclass.md`](../guides/auth/user-subclass.md).

## `sync_auth_user_model`

| Default | Env | Type |
|---------|-----|------|
| `true` | `MKS_CMS_SYNC_AUTH_USER_MODEL` | bool |

When `true`, MKSine sets:

- `auth.providers.users.model`
- `filament-shield.auth_provider_model`

…to whatever `mksine.user_model` resolves to **at boot time** (so plugin overrides win). Set `false` only if you intentionally maintain those keys yourself in `config/auth.php` or `.env`.

## `plugins_path`

| Default | Env | Type |
|---------|-----|------|
| `'plugins'` | `MKS_CMS_PLUGINS_PATH` | string |

Path **relative to `base_path()`** under which `mks-plugin:discover` scans for `plugin.php` manifests. Used everywhere the docs refer to `{plugin_root}`.

> **Caveat.** `mks-plugin:make` writes to `base_path('plugins/{name}')` and ignores this key (verified in [`PluginMakeCommand`](../../src/Console/Commands/PluginMakeCommand.php)). If you customise the path, scaffold first and move the directory before running `mks-plugin:discover`.

## `plugins.boot_guard_ttl`

| Default | Env | Type |
|---------|-----|------|
| `15` (seconds) | `MKS_CMS_PLUGIN_BOOT_GUARD_TTL` | int |

The plugin runtime sets a per-process flag while a plugin is booting. If the flag is older than this TTL on the next request, MKSine assumes the previous boot crashed and marks the plugin `boot_failed` so the panel keeps loading.

Tuning guidance:

- **Increase** if you have legitimate cold-boot cost (e.g. heavy plugin with cache warmup) and you see false `boot_failed` entries.
- **Do not lower** below your slowest realistic boot — you will start flapping plugins offline mid-request.

## `updater`

ZIP-based updater used by the Filament "Update" actions on the Plugins and Themes pages, the System Update page, and the matching CLI commands. See [ZIP updater](../operations/zip-updater.md).

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `enabled` | `MKS_CMS_UPDATER_ENABLED` | `true` | Master switch. When `false`, updater UI is hidden and CLI invocations fail early. |
| `keep_backups` | `MKS_CMS_UPDATER_KEEP_BACKUPS` | `3` | Number of historical backups kept per target under `{target-parent}/.mks-backups/`. Older entries are pruned after each successful update. |
| `max_zip_size_mb` | `MKS_CMS_UPDATER_MAX_ZIP_MB` | `256` | Hard upload cap per ZIP. Applies to both UI and CLI. |
| `lock_timeout_sec` | `MKS_CMS_UPDATER_LOCK_TTL` | `300` | Informational stale-lock threshold. `flock()` itself is non-blocking — the updater fails fast if another run is already holding the lock. |
| `allow_same_version_reinstall` | `MKS_CMS_UPDATER_ALLOW_REINSTALL` | `false` | When `true`, same-version uploads are accepted without `--force`. Useful only for recovery from corrupted files. |

Permissions: updater actions require the Shield Super Admin role (`config('filament-shield.super_admin.name')`, default `super_admin`). There is no way to relax this — only super admins can replace on-disk code.

Logs: `storage/logs/mksine-updates/{target}-{id}-{TS}.log` per run.

## `media`

Media-library configuration used by the Media resource and uploaders.

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `disk` | `MKS_CMS_MEDIA_DISK` | `'public'` | Must exist in `config('filesystems.disks')`. Use a private disk + `security.authorize_media = true` for paid content. |
| `path` | `MKS_CMS_MEDIA_PATH` | `'media'` | Subdirectory under the disk. |
| `max_size` | `MKS_CMS_MEDIA_MAX_SIZE` | `10240` (KB = 10 MB) | Hard cap enforced server-side. |
| `allowed_types` | _none_ | image/{jpeg,png,gif,webp,svg+xml}, video/{mp4,webm}, application/pdf, MS Word, MS Excel | Strict allowlist. Add types in your published config. |
| `optimize_images` | `MKS_CMS_OPTIMIZE_IMAGES` | `true` | Runs the configured image optimizer on upload. |
| `generate_thumbnails` | `MKS_CMS_GENERATE_THUMBNAILS` | `true` | Generates `thumbnail_sizes` variants on upload. |
| `thumbnail_sizes` | _none_ | `small=150x150`, `medium=300x300`, `large=600x600` | Override per project. |

Operational notes:

- Changing `disk` or `path` after files exist does not migrate them. Move existing files first, then change the config, then clear caches.
- `allowed_types` is checked by mime, not by extension. SVG uploads should be considered a security risk unless you also strip scripts (MKSine does not).

## `content`

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `post_statuses` | _none_ | `draft`, `published`, `archived` | Map of slug → label. |
| `enable_revisions` | `MKS_CMS_ENABLE_REVISIONS` | `false` | Reserved for the upcoming revisions feature. |
| `max_revisions` | `MKS_CMS_MAX_REVISIONS` | `10` | Reserved. |
| `enable_scheduling` | `MKS_CMS_ENABLE_SCHEDULING` | `true` | Lets posts have a future `published_at`. |

## `hooks`

The hook system and its async dispatcher.

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `discovery_paths` | _none_ | `[]` | Extra **absolute** directories scanned by `mks:discover`. The package always scans `Core/Listeners` first; missing paths are skipped with a warning. |
| `log_slow_hooks` | `MKS_CMS_LOG_SLOW_HOOKS` | `true` | Writes a `warning` log when a single listener exceeds `slow_hook_threshold`. |
| `slow_hook_threshold` | `MKS_CMS_SLOW_HOOK_THRESHOLD` | `100` (ms) | Threshold for the slow-hook log. |
| `cache_discovery` | `MKS_CMS_CACHE_HOOK_DISCOVERY` | `true` | When `true`, discovery results are cached using the `cache.*` settings. Always `false` in tests. |
| `queue.enabled` | `MKS_CMS_HOOKS_QUEUE_ENABLED` | `true` | Allows listeners that opt into [`QueueableHookEventInterface`](contracts.md#queueablehookeventinterface) to dispatch async. Set `false` to force everything synchronous. |
| `queue.connection` | `MKS_CMS_HOOKS_QUEUE_CONNECTION` | `null` | Laravel queue connection name. `null` → default. |
| `queue.queue` | `MKS_CMS_HOOKS_QUEUE_NAME` | `null` | Queue name override. |
| `queue.tries` | `MKS_CMS_HOOKS_QUEUE_TRIES` | `3` | Worker retry count. |
| `queue.backoff` | `MKS_CMS_HOOKS_QUEUE_BACKOFF` | `60` (s) | Delay between retries. |
| `queue.timeout` | `MKS_CMS_HOOKS_QUEUE_TIMEOUT` | `120` (s) | Per-job timeout. |

See [Async hooks](../guides/hooks/async-and-queues.md) for the runtime behaviour.

## `security`

| Key | Env | Default | Notes |
|-----|-----|---------|-------|
| `authorize_media` | `MKS_CMS_AUTHORIZE_MEDIA` | `false` | When `true`, media downloads run through the configured policy. Required when serving from a private disk. |
| `sanitize_filenames` | `MKS_CMS_SANITIZE_FILENAMES` | `true` | Replaces unsafe characters at upload time. Leave on. |
| `scan_uploads` | `MKS_CMS_SCAN_UPLOADS` | `false` | Reserved for an external malware scanner integration; currently a no-op. |

## `country_dial_codes`

A static map (`'+CC'` → `'Country (+CC)'`) used by phone-number form fields. Override or extend in your published config; do not delete the key (the field expects an array). Duplicate keys (`+1`, `+44`, `+7`, `+44`, `+212`, `+590`, `+262`) are inherent to the ITU plan — only the last entry per key wins.

## `api`

Reserved for the future REST surface. None of these keys are consumed by current code paths.

| Key | Env | Default |
|-----|-----|---------|
| `enabled` | `MKS_CMS_API_ENABLED` | `false` |
| `prefix` | `MKS_CMS_API_PREFIX` | `'api/cms'` |
| `version` | _none_ | `'v1'` |
| `rate_limit` | `MKS_CMS_API_RATE_LIMIT` | `60` |

## Auxiliary env vars (not in `config/mksine.php`)

These are read directly by package code without a corresponding config entry. Treat them as part of the package’s public configuration surface.

### Setup database (used by `mksine:fresh-super-admin`)

The setup connection is registered as `mksine_setup` in your `config/database.php`. It must be **distinct** from the app default, and one of `sqlite`, `mysql`, `mariadb`.

| Env var | Purpose |
|---------|---------|
| `MKSINE_SETUP_DB_DRIVER` | Driver for the setup connection (`sqlite`, `mysql`, `mariadb`). |
| `MKSINE_SETUP_DB_DATABASE` | DB name (MySQL) or path (SQLite). Required. |
| `MKSINE_SETUP_DB_HOST` | Optional, falls back to `DB_HOST`. |
| `MKSINE_SETUP_DB_PORT` | Optional, falls back to `DB_PORT`. |
| `MKSINE_SETUP_DB_USERNAME` | Optional, falls back to `DB_USERNAME`. |
| `MKSINE_SETUP_DB_PASSWORD` | Optional, falls back to `DB_PASSWORD`. |
| `MYSQLDUMP_PATH` | Override the `mysqldump` binary used for portable dumps. |

See [`mksine:fresh-super-admin`](commands.md#mksinefresh-super-admin).

## Cache invalidation cheatsheet

Whenever you change config or code that the package caches, run the matching command:

| Change | Commands |
|--------|----------|
| `config/mksine.php` (any key) | `php artisan config:clear` (or `config:cache` after) |
| Plugin manifest, new plugin folder | `php artisan mks-plugin:discover --clear` |
| Hook listener classes / `hooks.discovery_paths` | `php artisan mks:discover` |
| Theme files added/removed | `php artisan mks:theme-publish` (and clear theme cache via `ThemeManager::clearCache()` programmatically if needed) |

## See also

- [Commands reference](commands.md) — what each command actually consumes from these keys.
- [Stability](stability.md) — which of these keys are part of the public contract.
- [Operations: deployment & hosting](../operations/deployment-hosting.md) — env-vs-host-app-vs-package separation.
