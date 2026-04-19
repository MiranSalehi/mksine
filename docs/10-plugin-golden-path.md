# Plugin golden path

End-to-end path from zero to a **working Filament resource** in the admin panel. Commands are run from the **Laravel application root** (where `artisan` lives).

**`{plugin_root}`** = `base_path(config('mksine.plugins_path'))` (see [00-overview.md](00-overview.md)).

## 0. Preconditions

- `miran/mksine` installed and the CMS panel works.
- Database migrated for the main app (including MKSine tables such as `mks_plugins`).
- You have a Super Admin (or equivalent) to assign permissions to new resources.

## 1. Scaffold the plugin

```bash
php artisan mks-plugin:make my-plugin \
  --author="Your Name" \
  --description="Short description"
```

Rules for the name: **lowercase**, alphanumeric + hyphens only (e.g. `my-plugin`).

This creates `{plugin_root}/my-plugin/` with `plugin.php`, a `PluginInterface` implementation, Filament folders, `package.json` + Vite stub, etc.

## 2. Discover

```bash
php artisan mks-plugin:discover
# or after cache issues:
php artisan mks-plugin:discover --clear
```

Commit policy for `bootstrap/cache/mks_plugins_discovery.php` is team-specific; many teams commit it so CI and deploys see the same discovery snapshot.

## 3. Install and activate

```bash
php artisan mks-plugin:install my-plugin
php artisan mks-plugin:activate my-plugin
```

If the plugin fails to boot, check `storage/logs/laravel.log` and the `mks_plugins` table boot error column.

## 4. Model and migration (optional but typical)

```bash
php artisan mks-plugin:make-model my-plugin Item --migration
php artisan mks-plugin:migrate my-plugin
```

## 5. Filament resource

```bash
php artisan mks-plugin:make-resource my-plugin Item --model=Item
```

Register navigation/permissions per your Shield setup (generate permissions / assign role as you do for other resources).

## 6. Frontend assets (when you have CSS/JS)

From the plugin directory:

```bash
cd {plugin_root}/my-plugin
npm install
npm run build
```

From app root:

```bash
php artisan mks-plugin:publish my-plugin
```

**Filament warning:** Plugin CSS is loaded broadly. Avoid shipping a full Tailwind preflight bundle that overrides Filament; scope styles ([50-troubleshooting.md](50-troubleshooting.md)).

## 7. Translations

```bash
php artisan mks-plugin:publish-lang my-plugin
```

## 8. Git and deploy

Commit (minimum):

- `{plugin_root}/my-plugin/**` (source)
- `public/plugins/my-plugin/**` after publish (so production does not need `npm`)
- `lang/vendor/my-plugin/**` after `publish-lang` if you ship translations

Production typically runs `php artisan migrate` (and maybe `optimize`); it does **not** need `npm` or `composer` inside the plugin if the above artifacts are committed.

## 9. Class-based hook listeners (optional)

If you add `MksineListenerInterface` / form / table listener **classes** under the plugin, you **must** register their root directory in `config/mksine.php` → `hooks.discovery_paths`, then run:

```bash
php artisan mks:discover
```

See [20-hooks-contract.md](20-hooks-contract.md).

## Reference

- Full command list: [30-commands-reference.md](30-commands-reference.md).
