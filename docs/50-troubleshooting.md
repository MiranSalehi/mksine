# Troubleshooting

**`{plugin_root}`** = `base_path(config('mksine.plugins_path'))` — see [00-overview.md](00-overview.md).

## Plugin not found

- Run `php artisan mks-plugin:discover` (try `--clear`).
- Verify `{plugin_root}/{id}/plugin.php` exists and `id` matches folder name conventions.
- Check `bootstrap/cache/mks_plugins_discovery.php` is writable and up to date.

## Plugin boot failed

- Read `storage/logs/laravel.log` (stack trace).
- Inspect `mks_plugins` table: boot error message.
- Common causes: missing class in `plugin_class`, Composer autoload inside plugin not installed (`cd {plugin_root}/{id} && composer install`), syntax error in `boot()`.

## Hook listener not running

- **Class-based / DB hooks:** Confirm listener class lives under a path scanned by `mks:discover` (core `Core/Listeners` **or** `config('mksine.hooks.discovery_paths')`).
- Run `php artisan mks:discover` after code changes.
- Confirm `mks_hooks` table exists and row is `is_enabled` as expected (system hooks still run when flagged system).

## Form/table hook not applied

- Verify hook name matches what the resource calls (e.g. `post.form`).
- Distinguish DB-synced class listeners from runtime `ResourceHookManager` usage (see [20-hooks-contract.md](20-hooks-contract.md)).

## Admin 403 / cannot access panel

- User model must implement Filament user contract + Shield traits as configured.
- If plugin replaces user model, see [40-security-auth.md](40-security-auth.md).

## Assets missing / broken Filament styles

- Run `npm run build` in plugin, then `php artisan mks-plugin:publish {id}`.
- Commit `public/plugins/{id}/` for deploy-without-npm workflows.
- Avoid shipping Tailwind preflight that overrides Filament global styles.

## 404 for `/livewire/...` or other Laravel routes (no physical file)

- **nginx (or proxy) 404:** The request never reached `public/index.php`. Set **document root** to Laravel `public` and use a front-controller rule (e.g. `try_files $uri $uri/ /index.php?$query_string`). `.htaccess` does not apply to nginx.
- **Wrong web root:** If the site root is the repo root instead of `public`, virtual URLs such as `/livewire/livewire.js` will not exist on disk and will 404 unless rewritten.
- **`APP_DEBUG` vs script URL:** With `config('app.debug')` false, Livewire may serve `.min.js` only; cached config or mixed environments can cause a mismatch. Clear or rebuild config cache after `.env` changes.
- Full checklist: [60-deployment-hosting.md](60-deployment-hosting.md).

## Migrations

- Plugin: `php artisan mks-plugin:migrate {id}`.
- Full app: `php artisan migrate` depending on project setup.

## Deeper narrative

For long explanations (lifecycle, queues, prevention), see [archive/README-v1-monolithic.md](archive/README-v1-monolithic.md) until those sections are split into focused docs.
