---
title: Installation
description: Install miran/mksine into a Laravel + Filament 4 application and verify the panel.
order: 1
---

# Installation

MKSine is a **Filament 4** CMS package. You need a working Laravel application, an **admin Filament panel**, and **`MksinePlugin` registered on that panel** before permissions can be generated correctly.

## Requirements

- PHP **8.2** or newer (`packages/mksine/composer.json` enforces `^8.2`).
- Laravel **11+** with a relational database (MySQL 5.7+, PostgreSQL 10+, or SQLite 3.8+).
- **Filament 4** — installed via `miran/mksine` (pulled in as a dependency) and bootstrapped with a panel provider.
- Node 18+ if you intend to build plugin or theme assets locally (production deploys can ship pre-built assets — see [Operations: Release archive](operations/release-archive.md)).

## Install flow (order matters)

| Step | What | Command / action |
|------|------|------------------|
| 1 | Require the package | `composer require miran/mksine` |
| 2 | Create the Filament admin panel | `php artisan filament:install --panels` |
| 3 | Register `MksinePlugin`; **remove** `Dashboard::class` from provider | Edit `AdminPanelProvider` (see below) |
| 4 | Publish assets, migrate, generate permissions | `php artisan mksine:install --migrate` |
| 5 | Create a super admin | `php artisan mksine:create-super-admin` |
| 6 | Smoke test | Visit `/admin` |

> **Why step 3 comes before step 4.** `mksine:install --migrate` runs `shield:generate --all`, which scans the **registered** Filament panel for resources, pages, and widgets. If `MksinePlugin` is not on the panel yet, MKSine permissions and policies are **not** created. You would need to run `php artisan shield:generate --all --panel=admin` again after registering the plugin.

## 1. Install the Composer package

```bash
composer require miran/mksine
```

If you develop the package as a path repository inside a monorepo, declare it in your application’s `composer.json` as a `path` repo and require `miran/mksine: @dev` (this monorepo already does that).

## 2. Create the Filament admin panel

On a **new** Laravel app that does not yet have Filament panels:

```bash
php artisan filament:install --panels
```

This creates `app/Providers/Filament/AdminPanelProvider.php` with panel id **`admin`** and path **`/admin`**, and registers the provider in `bootstrap/providers.php`.

If you already have a panel with a different id, use that id consistently in `shield:generate --panel=…` and in your panel provider. MKSine’s installer defaults to `--panel=admin`.

Alternative when you only need an extra panel:

```bash
php artisan make:filament-panel admin
```

## 3. Register the Filament plugin

`filament:install --panels` scaffolds `AdminPanelProvider` with Filament’s default dashboard page. **Remove it** — MKSine ships its own hookable dashboard (`MksineDashboard`). Keeping both breaks the `mksine-dashboard` route and causes HTTP 500 on `/admin`.

In `app/Providers/Filament/AdminPanelProvider.php`:

```php
use Miran\Mksine\MksinePlugin;
// Do not import Filament\Pages\Dashboard — MKSine replaces it.

public function panel(Panel $panel): Panel
{
    return $panel
        ->id('admin')
        ->path('admin')
        ->login()
        ->plugins([
            MksinePlugin::make(),
        ])
        // Remove the block filament:install added:
        // ->pages([
        //     Dashboard::class,
        // ])
        ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
        ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
        ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets');
}
```

> **Required.** Delete `->pages([Dashboard::class])` (and the `use Filament\Pages\Dashboard` import if unused). Do not register both `Dashboard::class` and `MksinePlugin` on the same panel.

`MksinePlugin`:

- Registers CMS resources (posts, categories, media, menus, settings, languages, plugins manager, …).
- Registers **Filament Shield** on the panel (you do **not** need a separate `FilamentShieldPlugin::make()` in the host app).
- Registers `MksineDashboard` as the panel home at `/admin` (hookable dashboard with sidebar groups and MKSine styling).
- Discovers Filament components from **active** CMS plugins and themes.

## 4. Run the MKSine installer

```bash
php artisan mksine:install --migrate
```

With admin credentials in one step (non-interactive):

```bash
php artisan mksine:install --migrate \
  --admin-email=admin@example.com \
  --admin-password='your-secure-password' \
  --admin-name="Admin"
```

### What `mksine:install --migrate` does automatically

1. Publishes `app/Models/User.php` (if missing, unless you use `--force`).
2. Publishes MKSine config, migrations, translations, and fonts.
3. Publishes **Filament Shield** and **Spatie Permission** config and migrations when Shield is installed.
4. Clears Laravel and Filament caches (`optimize:clear`, `filament:optimize-clear`).
5. Runs `php artisan migrate`.
6. Publishes Filament panel assets (`filament:assets`).
7. Runs `shield:generate --all --panel=admin` — **only when** the `admin` panel exists and `MksinePlugin` is registered.
8. Runs `mks:discover` (syncs hook listeners to `mks_hooks`).
9. Optionally creates a super admin when `--admin-email` and `--admin-password` are passed.

### Manual publish (equivalent to steps 1–3 and 5 above)

```bash
php artisan vendor:publish --provider="Miran\Mksine\MksineServiceProvider" --tag="mksine-config"
php artisan vendor:publish --provider="Miran\Mksine\MksineServiceProvider" --tag="mksine-migrations"
php artisan vendor:publish --provider="Miran\Mksine\MksineServiceProvider" --tag="mksine-lang"
php artisan vendor:publish --provider="Miran\Mksine\MksineServiceProvider" --tag="mksine-fonts"
php artisan vendor:publish --tag="filament-shield-config"
php artisan vendor:publish --tag="permission-config"
php artisan vendor:publish --tag="permission-migrations"
php artisan optimize:clear
php artisan filament:optimize-clear
php artisan migrate
php artisan filament:assets
php artisan shield:generate --all --panel=admin
php artisan mks:discover
```

The installer also publishes a `User` model into `app/Models/User.php` if your app does not already have one configured for MKSine. See [Auth: User subclass](guides/auth/user-subclass.md) before you change the user class.

## 5. Create a super admin

If you did not pass `--admin-email` / `--admin-password` on install:

```bash
php artisan mksine:create-super-admin
```

`mksine:create-super-admin` creates a user on your app database, ensures the `super_admin` role exists, syncs all permission rows onto that role, and assigns it to the user.

For a **portable empty database** (CI, greenfield export) use [`mksine:fresh-super-admin`](reference/commands.md#mksinefresh-super-admin) against the isolated `mksine_setup` connection — not the same as `mksine:create-super-admin`. See [Shield and policies](guides/auth/shield-and-policies.md).

For a full interactive Shield setup (panel plugin registration, optional tenancy), you can still use `php artisan shield:setup` instead of the commands above.

## 6. Smoke test the panel

1. Visit the Filament panel URL (commonly `/admin`).
2. Log in with the super admin you created.
3. Confirm the navigation contains MKSine sections (for example **Plugins**, **Media**, **Menus**, **Settings**, **Languages**) under appropriate Shield permissions.
4. Confirm MKSine admin styling loaded (sidebar groups, fonts, spacing). If the panel looks like a bare Filament skeleton, run `php artisan filament:assets` and hard-refresh the browser. See [Troubleshooting: Admin styles missing](operations/troubleshooting.md#admin-styles-missing-mksine-css).
5. Run the [validation checklist](operations/validation-checklist.md) before considering the install complete.

## What just got installed

| Area | Source of truth |
|------|------------------|
| Config | [`packages/mksine/config/mksine.php`](../config/mksine.php) → published to `config/mksine.php` |
| Migrations | `packages/mksine/database/migrations/` → published to `database/migrations/` |
| Shield / permissions | `filament-shield-config`, `permission-config`, `permission-migrations` → `config/filament-shield.php`, `config/permission.php`, `database/migrations/*_create_permission_tables.php` |
| Translations | `packages/mksine/resources/lang/` → published to `lang/vendor/mksine/` |
| Fonts | `packages/mksine/resources/fonts/` → published to `public/vendor/mksine/fonts/` |
| Filament plugin | `Miran\Mksine\MksinePlugin` (registered in your panel provider) |
| Policies | `app/Policies/*` — generated by `shield:generate` |

## After enabling a new plugin

Activating a CMS plugin does **not** regenerate Shield permissions. After `mks-plugin:activate {id}`:

```bash
php artisan shield:generate --all --panel=admin
php artisan mks:discover
```

See [Shield and policies](guides/auth/shield-and-policies.md).

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
