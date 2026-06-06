---
title: Installation
description: Install miran/mksine into a Laravel + Filament 4 application and verify the panel.
order: 1
---

# Installation

These steps assume an existing Laravel application with **Filament 4** already wired (admin panel provider registered). If you do not have a Laravel app yet, follow the upstream Laravel and Filament install guides first; MKSine does not bootstrap a Laravel app for you.

## Requirements

- PHP **8.2** or newer (`packages/mksine/composer.json` enforces `^8.2`).
- Laravel **11+** with Filament **4.x** installed and a panel provider registered.
- A relational database supported by Laravel (MySQL 5.7+, PostgreSQL 10+, or SQLite 3.8+).
- Node 18+ if you intend to build plugin or theme assets locally (production deploys can ship pre-built assets — see [Operations: Release archive](operations/release-archive.md)).

## 1. Install the Composer package

```bash
composer require miran/mksine
```

If you develop the package as a path repository inside a monorepo, declare it in your application’s `composer.json` as a `path` repo and require `miran/mksine: @dev` (this monorepo already does that).

## 2. Publish package files and run migrations

The packaged installer publishes MKSine assets, **Filament Shield / Spatie Permission** config and migrations (roles & permissions tables), and optionally runs `migrate` in one step:

```bash
php artisan mksine:install --migrate
```

If you prefer to publish each tag manually:

```bash
php artisan vendor:publish --provider="Miran\Mksine\MksineServiceProvider" --tag="mksine-config"
php artisan vendor:publish --provider="Miran\Mksine\MksineServiceProvider" --tag="mksine-migrations"
php artisan vendor:publish --provider="Miran\Mksine\MksineServiceProvider" --tag="mksine-lang"
php artisan vendor:publish --provider="Miran\Mksine\MksineServiceProvider" --tag="mksine-fonts"
php artisan vendor:publish --tag="filament-shield-config"
php artisan vendor:publish --tag="permission-config"
php artisan vendor:publish --tag="permission-migrations"
php artisan migrate
```

The installer also publishes a `User` model into `app/Models/User.php` if your app does not already have one configured for MKSine. See [Auth: User subclass](guides/auth/user-subclass.md) before you change the user class.

## 2b. Shield permissions and super admin

`mksine:install` does **not** generate Filament permissions or policies. After migrate, run:

```bash
php artisan shield:generate --all
php artisan mksine:create-super-admin
```

`mksine:create-super-admin` creates a user on your app database, ensures the `super_admin` role exists, syncs all permission rows onto that role, and assigns it to the user. For an interactive Shield setup (panel plugin registration, optional tenancy), you can still use `php artisan shield:setup` instead of the two commands above.

For a **portable empty database** (CI, greenfield export) use [`mksine:fresh-super-admin`](reference/commands.md#mksinefresh-super-admin) against the isolated `mksine_setup` connection — not the same as `mksine:create-super-admin`. See [Shield and policies](guides/auth/shield-and-policies.md).

## 3. Register the Filament plugin

In your panel provider (typically `app/Providers/Filament/AdminPanelProvider.php`):

```php
use Miran\Mksine\MksinePlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... existing configuration
        ->plugins([
            MksinePlugin::make(),
        ]);
}
```

`MksinePlugin` registers the CMS resources (posts, categories, media, menus, settings, languages, plugins manager) and wires Filament Shield. If your panel does not already include Shield, MKSine will register the Shield plugin internally; if Shield is already registered explicitly, MKSine detects and skips its own registration.

## 4. Discover hook listeners

Class-based hook listeners are synced to the `mks_hooks` table by:

```bash
php artisan mks:discover
```

You only need this for the **discovery** family of hooks. Runtime hook registration via the `Hooks::` static helper does not require this command. See [Hook overview](guides/hooks/overview-two-families.md).

If your application or its plugins ship listeners outside the package’s `Core/Listeners` directory, add their roots to `config/mksine.php`:

```php
'hooks' => [
    'discovery_paths' => [
        base_path('app/Hooks/Listeners'),
        base_path(config('mksine.plugins_path').'/my-plugin/src/Listeners'),
    ],
    // ...
],
```

## 5. Smoke test the panel

1. Visit the Filament panel URL (commonly `/admin`).
2. Confirm the navigation contains MKSine sections (for example **Plugins**, **Media**, **Menus**, **Settings**, **Languages**) under appropriate Shield permissions.
3. Run the [validation checklist](operations/validation-checklist.md) before considering the install complete.

## What just got installed

| Area | Source of truth |
|------|------------------|
| Config | [`packages/mksine/config/mksine.php`](../config/mksine.php) → published to `config/mksine.php` |
| Migrations | `packages/mksine/database/migrations/` → published to `database/migrations/` |
| Shield / permissions | `filament-shield-config`, `permission-config`, `permission-migrations` → `config/filament-shield.php`, `config/permission.php`, `database/migrations/*_create_permission_tables.php` |
| Translations | `packages/mksine/resources/lang/` → published to `lang/vendor/mksine/` |
| Fonts | `packages/mksine/resources/fonts/` → published to `public/vendor/mksine/fonts/` |
| Filament plugin | `Miran\Mksine\MksinePlugin` (this is what you `make()` in your panel provider) |

## Optional: change the user model

Set `MKS_CMS_USER_MODEL` (or edit `config/mksine.php` → `user_model`) to a class that extends your application user. By default MKSine also keeps `auth.providers.users.model` and `filament-shield.auth_provider_model` in sync via `sync_auth_user_model = true`. See [User subclass](guides/auth/user-subclass.md).

## Optional: enable additional features

`config/mksine.php` ships with **theme management** and **page builder** disabled. Enable them only when you have read the corresponding guides:

```php
'features' => [
    'theme_management' => true, // see guides/themes/
    'page_builder'     => true, // see guides/page-builder/
    // ...
],
```

Equivalent env keys: `MKS_CMS_THEME_MANAGEMENT`, `MKS_CMS_PAGE_BUILDER`. Full table in [Configuration](reference/configuration.md).

## Optional: global geo data

Required when a plugin (for example **ecom**) uses multi-country addresses or checkout geo selects. Pure CMS installs can skip this.

```bash
php artisan mks:geo:import          # needs outbound HTTP; cities need a MySQL locations DB
```

Then open **System → Settings → Geo** and choose enabled countries and the default country.

See [Global geo system](guides/geo/overview.md) and [Import and migration](guides/geo/import-and-migration.md).

## Next steps

- [Quickstart](02-quickstart.md) — write a working plugin in a few minutes.
- [Concepts: Architecture](concepts/architecture.md) — understand what got registered and why.
