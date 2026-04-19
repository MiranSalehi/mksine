# MKSine

> **Development status**  
> This package is under active development. Use in production only after thorough testing.

MKSine is a Laravel + **Filament 4** CMS foundation: content, themes, plugins, hooks, and a visual page builder. This repository root file is a **short entry point**; **maintained developer documentation** lives in **[`docs/`](docs/00-overview.md)**.

## Developer documentation (canonical)

| Doc | Purpose |
|-----|---------|
| [docs/00-overview.md](docs/00-overview.md) | Map of all docs |
| [docs/10-plugin-golden-path.md](docs/10-plugin-golden-path.md) | From scaffold to working plugin |
| [docs/20-hooks-contract.md](docs/20-hooks-contract.md) | DB-synced vs runtime hooks + `discovery_paths` |
| [docs/30-commands-reference.md](docs/30-commands-reference.md) | `mks-plugin:*`, `mks:*`, themes |
| [docs/40-security-auth.md](docs/40-security-auth.md) | User subclass, Shield, morph |
| [docs/50-troubleshooting.md](docs/50-troubleshooting.md) | Common failures |
| [docs/60-deployment-hosting.md](docs/60-deployment-hosting.md) | Production web server, document root, release archive `public/` rules |
| [docs/99-validation-checklist.md](docs/99-validation-checklist.md) | Done criteria |
| [docs/DEVELOPER-SLO.md](docs/DEVELOPER-SLO.md) | Audience and SLO |

**Architecture decisions:** [docs/adr/](docs/adr/)

**Historical monolith (single long README):** [docs/archive/README-v1-monolithic.md](docs/archive/README-v1-monolithic.md) — use for deep narrative (hook lifecycle, page builder, menus, themes) until those sections are split further.

## Features (summary)

- Page builder, themes, plugins (`mks-plugin:*`), hook system (`mks:discover`), menus, media, settings, Shield-oriented permissions.

## Requirements

- PHP **8.2+** (`composer.json`: `^8.2`)
- Laravel **11+** compatible with **Filament 4**
- MySQL 5.7+, PostgreSQL 10+, or SQLite 3.8+

## Installation

### Composer

```bash
composer require miran/mksine
```

### Publish and migrate

```bash
php artisan mksine:install --migrate
```

Or manually:

```bash
php artisan vendor:publish --provider="Miran\Mksine\MksineServiceProvider" --tag="mksine-config"
php artisan vendor:publish --provider="Miran\Mksine\MksineServiceProvider" --tag="mksine-migrations"
php artisan migrate
```

### Register the Filament plugin

In `app/Providers/Filament/AdminPanelProvider.php`:

```php
use Miran\Mksine\MksinePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            MksinePlugin::make(),
        ]);
}
```

### Discover hooks (class-based listeners)

```bash
php artisan mks:discover
```

Plugin/app listener directories must be listed under `config('mksine.hooks.discovery_paths')` when they live outside package `Core/Listeners`. See [docs/20-hooks-contract.md](docs/20-hooks-contract.md).

### User model and auth

`config/mksine.php` → **`user_model`** (default `App\Models\User`). When **`sync_auth_user_model`** is `true`, the package aligns `auth.providers.users.model` and `filament-shield.auth_provider_model`. Plugins that replace the user class must set all three in `boot()` — see [docs/40-security-auth.md](docs/40-security-auth.md).

## License

See [LICENSE.md](LICENSE.md).
