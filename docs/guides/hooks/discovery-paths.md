---
title: Discovery paths
---

# Discovery paths

`mks:discover` is the only command that turns a class on disk into a row in `mks_hooks`. It only scans paths it has been explicitly told to scan. **No path = no DB sync.**

## What gets scanned

`DiscoverHooksCommand::resolveDiscoveryPaths()` (see `src/Console/Commands/DiscoverHooksCommand.php`) returns:

1. The package’s own `Core/Listeners` directory (resolved from inside the vendored package via `realpath(__DIR__.'/../../Core/Listeners')`). This is **always** scanned.
2. Every entry in `config('mksine.hooks.discovery_paths')` that exists on disk. Missing entries are skipped with a `warn()`.

That’s it. The scanner does not crawl `app/`, `plugins/`, or anywhere else by default. If your plugin lives under a directory not listed in `discovery_paths`, **its listener classes will not be indexed**, even if they implement the right interfaces.

## How paths are resolved

Each entry can be:

- **Absolute** — used verbatim (after `realpath` validation).
- **Relative** — resolved through `base_path($entry)` (so the project root is the anchor; do not put a leading `/` for relative paths).

```php
// config/mksine.php
'hooks' => [
    'discovery_paths' => [
        // absolute (preferred for plugins)
        base_path(config('mksine.plugins_path').'/my-plugin/src/Hooks/Listeners'),

        // relative — resolved as base_path('app/Hooks/Listeners')
        'app/Hooks/Listeners',
    ],
],
```

> Use `base_path(config('mksine.plugins_path').'/…')` for plugin paths so a host that customised `plugins_path` keeps working. Hardcoding `base_path('plugins/…')` is a latent bug.

## Recommended layout

Keep the listener tree narrow:

```
{plugin_root}/my-plugin/src/Hooks/Listeners/
{plugin_root}/my-plugin/src/Hooks/Forms/
{plugin_root}/my-plugin/src/Hooks/Tables/
```

Then list **only those three directories** in `discovery_paths`. Pointing the scanner at the whole `src/` makes every PHP file get reflected, even helpers and value objects, which is wasteful and noisy in error reports.

## Re-running

Run `php artisan mks:discover` after:

- Adding or removing a listener class.
- Renaming a listener class (rows are keyed by FQCN; rename = old row stays as orphan unless your sync logic deletes missing classes).
- Editing `mksine.hooks.discovery_paths`.
- Deploying.

The command is **idempotent** — re-running it on an unchanged tree produces no DB writes. Existing `is_enabled` flags and priority overrides are preserved across runs (the command syncs definitions, not state).

## Failure modes

| Symptom                                                       | Cause                                                                 | Fix                                                                                          |
| ------------------------------------------------------------- | --------------------------------------------------------------------- | -------------------------------------------------------------------------------------------- |
| `Skipping missing or invalid discovery path: …`               | The configured path does not exist on disk.                           | Fix the config entry or remove it.                                                           |
| `mks_hooks table does not exist. Please run migrations first.` | App migrations haven’t run yet on this environment.                  | `php artisan migrate`, then re-run.                                                          |
| `No listeners or hooks found.`                                | The scanned directories contain no class implementing the interfaces. | Confirm the namespace matches PSR-4, the file is `.php`, and the class is `final`/concrete.  |
| Class lives under the path but doesn’t appear in `mks_hooks`  | Class is abstract, doesn’t implement an interface, or has a syntax error that prevents reflection. | Fix the class. The discovery service silently skips classes it cannot reflect; check the laravel.log for warnings. |

## Performance

Discovery walks every PHP file under each path and instantiates `ReflectionClass` on each. On a tree with hundreds of files, this can take a few hundred ms. Run it during deploys, **not** as part of a regular request. There is no auto-discovery on boot.

> The package config exposes `mksine.hooks.cache_discovery` and a `cache.*` block, but the current discovery command does not consume them. They are documented in `config/mksine.php` for future use; don’t rely on them today. Treat `mks:discover` as a **deploy-time** command and budget accordingly.

## Discovery vs runtime registration

This is a frequent source of confusion:

- `mks:discover` populates `mks_hooks`. It does **not** register listeners with `HookManager`.
- `Hooks::register('event.name', ListenerClass::class, $priority)` registers the binding with `HookManager`. It does **not** populate `mks_hooks`.

You typically need **both**:

```php
// In your plugin's boot()
Hooks::register('post.created', NotifySlackListener::class, 50);
```

…**and** the directory containing `NotifySlackListener` listed in `discovery_paths`. The first call wires the dispatcher; the second lets `mks:discover` give the operator a row they can toggle.

The exception: form/table listener interfaces have `getFormName()` / `getTableName()` self-describing methods, so they can be auto-bound by the discovery service without an explicit `Hooks::extendForm(…)` call. Event listeners always need both halves.

## See also

- [Two hook families](overview-two-families.md)
- [Event hooks](event-hooks.md)
- Reference: [`mks:discover`](../../reference/commands.md#mksdiscover), [`hooks.discovery_paths`](../../reference/configuration.md#hooks)
