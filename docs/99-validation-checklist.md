# Validation checklist (human or AI)

Use this after following [10-plugin-golden-path.md](10-plugin-golden-path.md) or when onboarding a new environment.

## Environment

- [ ] PHP version matches package constraint (`^8.2`).
- [ ] Laravel + Filament versions match the host app’s declared constraints.
- [ ] Database reachable; `php artisan migrate` (or project equivalent) succeeds for core app.

## Production / web server

- [ ] HTTP document root is Laravel **`public/`** (contains `index.php` and `.htaccess`).
- [ ] **Apache:** `mod_rewrite` / `AllowOverride` allow `public/.htaccess` rules (or equivalent virtual host config).
- [ ] **nginx:** `try_files` (or equivalent) sends non-file requests to `index.php`; `root` points at `public`.
- [ ] Smoke test: `GET /livewire/livewire.js` returns **200** when `APP_DEBUG=true`, or `GET /livewire/livewire.min.js` when `APP_DEBUG=false` (after `config:cache` if used).
- [ ] After changing `.env` on the server, run `php artisan config:clear` or `php artisan optimize:clear` as needed so `config:cache` does not serve stale `debug` / URL settings.

See [60-deployment-hosting.md](60-deployment-hosting.md).

## Plugin lifecycle

- [ ] `php artisan mks-plugin:discover` lists the new plugin (or cache file updated).
- [ ] `php artisan mks-plugin:install {id}` completes without exception.
- [ ] `php artisan mks-plugin:activate {id}` completes; `mks_plugins` shows active; no boot error.
- [ ] `php artisan mks-plugin:migrate {id}` succeeds if the plugin ships migrations.

## Admin UI

- [ ] Log in as a user with permission to open the new resource.
- [ ] New resource appears in navigation (or direct URL works).
- [ ] Create + list + edit smoke test passes.

## Assets and lang (if applicable)

- [ ] `npm run build` in plugin succeeds locally.
- [ ] `php artisan mks-plugin:publish {id}` ran; `public/plugins/{id}/` contains expected files.
- [ ] `php artisan mks-plugin:publish-lang {id}` ran if translations are used; `lang/vendor/{id}/` present.
- [ ] Planned git commit includes published artifacts if production has no Node.

## Hooks (if applicable)

- [ ] Listener classes live under a path covered by `mks:discover` (core or `hooks.discovery_paths`).
- [ ] `php artisan mks:discover` run after changes; expected rows in `mks_hooks` (or documented runtime-only path).

## Security

- [ ] No unauthorized access to new resources (Shield / policies).
- [ ] If user subclass used: auth + `mksine.user_model` + Shield provider model aligned ([40-security-auth.md](40-security-auth.md)).
