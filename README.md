# MKSine

> **Development status**
> This package is under active development. Use in production only after thorough testing.

MKSine is a Laravel + **Filament 4** CMS foundation: content, themes, plugins, hooks, menus, media, and a visual page builder.

This file is a **short entry point**. The **canonical developer documentation** lives under [`docs/`](docs/00-introduction.md) and is structured to power a future static documentation site without restructuring.

## Developer documentation (canonical)

The full sidebar lives in [`docs/_nav.yml`](docs/_nav.yml). Quick links:

| Section | Start here |
|---------|------------|
| Getting started | [Introduction](docs/00-introduction.md) → [Installation](docs/01-installation.md) → [Quickstart](docs/02-quickstart.md) |
| Plugin guides | [Plugin golden path](docs/guides/plugins/golden-path.md) |
| Hook guides | [Two families overview](docs/guides/hooks/overview-two-families.md) |
| Theme guides | [Creating a theme](docs/guides/themes/creating-a-theme.md) |
| Geo | [Global geo system](docs/guides/geo/overview.md) |
| Reference | [Commands](docs/reference/commands.md), [Configuration](docs/reference/configuration.md), [Contracts](docs/reference/contracts.md), [API stability](docs/reference/stability.md) |
| Operations | [Deployment and hosting](docs/operations/deployment-hosting.md), [Troubleshooting](docs/operations/troubleshooting.md), [Validation checklist](docs/operations/validation-checklist.md) |
| Project meta | [Versioning](docs/meta/versioning.md), [Upgrade guide](docs/meta/upgrade-guide.md), [SLO](docs/meta/slo.md) |

**Architecture decisions:** [docs/adr/](docs/adr/)

**Historical monolith** (single long README, kept until its sections are fully extracted): [docs/archive/README-v1-monolithic.md](docs/archive/README-v1-monolithic.md).

## Features (summary)

- Plugin system (`mks-plugin:*`), hook system (`mks:discover`), themes, page builder, menus, media, settings, global geo (countries/states/cities, `mks:geo:import`), Filament Shield permissions.

## Requirements

- PHP **8.2+** (`composer.json`: `^8.2`).
- Laravel **11+** compatible with **Filament 4**.
- MySQL 5.7+, PostgreSQL 10+, or SQLite 3.8+.

## Quick install

```bash
composer require miran/mksine
php artisan filament:install --panels
```

Register the plugin in `app/Providers/Filament/AdminPanelProvider.php`:

```php
use Miran\Mksine\MksinePlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugins([
        MksinePlugin::make(),
    ]);
}
```

Then finish setup:

```bash
php artisan mksine:install --migrate
php artisan mksine:create-super-admin
```

`mksine:install --migrate` publishes assets, migrates, clears caches, runs `shield:generate`, `mks:discover`, and `filament:assets` when the admin panel has `MksinePlugin` registered.

Full install steps and verification: [`docs/01-installation.md`](docs/01-installation.md).

## License

See [LICENSE.md](LICENSE.md).
