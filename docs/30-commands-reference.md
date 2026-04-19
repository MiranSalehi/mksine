# Commands reference

All commands below are defined in `miran/mksine` unless noted. Run from **application root**.

**Plugin source tree:** `{plugin_root}` = `base_path(config('mksine.plugins_path'))` — see [00-overview.md](00-overview.md).

## Plugins

| Command | Purpose |
|---------|---------|
| `mks-plugin:discover` | Scan each `{plugin_root}/*/plugin.php`; refresh discovery cache. `--clear` clears cache first. |
| `mks-plugin:list` | List plugins; `--status=` filter (`active`, `inactive`, `installed`, `not_installed`). |
| `mks-plugin:make` | Scaffold new plugin: `{name} [--namespace=] [--author=] [--description=]`. |
| `mks-plugin:install` | Install `{plugin}` (DB + lifecycle). |
| `mks-plugin:activate` | Activate `{plugin}` (installs if needed). |
| `mks-plugin:deactivate` | Deactivate `{plugin}`. |
| `mks-plugin:uninstall` | Uninstall `{plugin}`; `--delete-data` drops data when implemented. |
| `mks-plugin:migrate` | Run plugin migrations; `{plugin?}` optional for all installed. |
| `mks-plugin:publish` | Copy `resources/dist/` → `public/plugins/{id}/`; optional `{plugin}`; `--force`. |
| `mks-plugin:publish-lang` | Publish lang to `lang/vendor/{id}/`; optional `{plugin}`. |
| `mks-plugin:make-model` | `{plugin} {name}`; `--migration` adds migration in plugin. |
| `mks-plugin:make-resource` | `{plugin} {name}`; `--model=` defaults to resource name. |
| `mks-plugin:make-page` | `{plugin} {name}`. |
| `mks-plugin:make-widget` | `{plugin} {name}`; `--chart`, `--stats`. |

**Not in core:** `mks-plugin:publish-vendor`. Vendor file materialization is implemented per plugin (custom Artisan command + `PluginVendorPublishRunner`).

## Hooks

| Command | Purpose |
|---------|---------|
| `mks:discover` | Sync class-based hook listeners from core + `hooks.discovery_paths` into `mks_hooks`. |

## Themes

| Command | Purpose |
|---------|---------|
| `mks:make-theme` | Scaffold theme. |
| `mks:theme-publish` | Publish built theme assets. |
| `mks:theme-publish-lang` | Publish theme translations. |

## Release (host project)

| Command | Purpose |
|---------|---------|
| `mks:release-archive` | Build deployable zip of host app (see command help for `--skip-build`, `--dry-run`). Only a subset of `public/` is included in the archive; see [60-deployment-hosting.md](60-deployment-hosting.md). |

## Typical sequences

**New plugin first run:**

```text
mks-plugin:discover → mks-plugin:install → mks-plugin:activate → mks-plugin:migrate
```

**After adding hook listener classes:**

```text
(configure hooks.discovery_paths if needed) → mks:discover
```

**Ship assets to production git:**

```text
(cd {plugin_root}/{id} && npm run build) → mks-plugin:publish {id} → mks-plugin:publish-lang {id}
```
