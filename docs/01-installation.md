---
title: Installation
description: Install miran/mksine into a Laravel + Filament 4 or 5 application and verify the panel.
order: 1
---

# Installation

MKSine is a **Filament 4 / 5** CMS package. You need a working Laravel application, an **admin Filament panel**, and **`MksinePlugin` registered on that panel** before permissions can be generated correctly.

## Requirements

- PHP **8.4** or newer (`packages/mksine/composer.json` enforces `^8.4`).
- Laravel **11+** with a relational database (MySQL 5.7+, PostgreSQL 10+, or SQLite 3.8+). For **Filament 5**, Laravel **11.28+**, Livewire **4**, and Tailwind CSS **4**.
- **Filament 4 or 5** — installed via `miran/mksine` (`filament/filament: ^4.0|^5.0`) and bootstrapped with a panel provider.
- Node 18+ if you intend to build plugin or theme assets locally (production deploys can ship pre-built assets — see [Operations: Release archive](operations/release-archive.md)).

## Install flow (order matters)

| Step | What | Command / action |
|------|------|------------------|
| 1 | Require the package | `composer require miran/mksine` |
| 2 | Create the Filament admin panel | `php artisan filament:install --panels` |
| 3 | Install | `php artisan mksine:install --migrate` (registers `MksinePlugin`, removes the default `/` route, publishes, migrates, activates the theme, creates the admin) |
| 4 | Smoke test | `php artisan mksine:doctor`, then visit `/admin` and `/` |

> **Permissions and the panel provider.** `shield:generate --all` scans the **registered** Filament panel. When the installer adds `MksinePlugin` to `AdminPanelProvider`, the panel in that process was already built without it, so the installer runs `shield:generate` in a fresh PHP process. Where `proc_open` is disabled, that is skipped with a warning; run `php artisan mksine:install --migrate` (or `shield:generate --all --panel=admin`) once more.

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

### Remove the default homepage route from routes/web.php

`php artisan filament:install --panels` does **not** touch `routes/web.php`. A fresh Laravel app still ships with:

```php
Route::get('/', function () {
    return view('welcome');
});
```

**Delete that route.** MKSine registers the public storefront homepage at `/` (permalink key `home_page_url`, route name `home`) from `packages/mksine/routes/web.php`. If the Laravel welcome route remains, `/` serves the framework skeleton instead of the active theme, and admin **View site** / `route('home')` links will not match what you expect.

Keep any other app-specific routes in `routes/web.php` (profile APIs, custom endpoints, etc.). Only remove the default `/` handler.

Until you assign a **Front Page** under **Settings → Permalinks**, the bundled `mksine` theme shows a **placeholder index** (content zones only—not demo marketing copy). Publish a page and select it as the front page when you are ready for real homepage content.

`MksinePlugin`:

- Registers CMS resources (posts, categories, media, menus, settings, languages, plugins manager, …).
- Registers **Filament Shield** on the panel (you do **not** need a separate `FilamentShieldPlugin::make()` in the host app).
- Registers `MksineDashboard` as the panel home at `/admin` (hookable dashboard with sidebar groups and MKSine styling).
- Discovers Filament components from **active** CMS plugins and themes.

> **User model (important).** Every Laravel app ships an `app/Models/User.php` that lacks Shield/roles. `mksine:install` **patches it automatically**, adding the `Filament\Models\Contracts\FilamentUser` contract and the `Miran\Mksine\Concerns\InteractsWithMksine` trait (a timestamped backup is written first). Without these, only **Dashboard** appears in the admin menu. If the auto-patch can't run, add them manually — see [User subclass](guides/auth/user-subclass.md).

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

The installer runs a pipeline of steps ([`InstallPipeline::DEFAULT_STEPS`](../src/Core/Install/InstallPipeline.php)). Each step can safely run twice, and the run stops at the first failure with a non-zero exit code. Continue after fixing the problem with `--resume`.

1. Checks PHP version, extensions and writable paths.
2. Writes `.env` values passed with `--app-url` / `--db-*` and generates `APP_KEY` when it is empty.
3. Patches `app/Models/User.php` (or publishes the package model when missing).
4. Registers `MksinePlugin` on `app/Providers/Filament/AdminPanelProvider.php` and removes Laravel's stock welcome route from `routes/web.php` (both with backups).
5. Publishes MKSine config, migrations, translations and fonts, plus Filament Shield and Spatie Permission files.
6. Clears Laravel and Filament caches.
7. Runs migrations. With `--profile=shared` they run one file at a time.
8. Publishes Filament panel and theme assets.
9. Activates `--theme` (or the default theme when none is active) and `--plugins`.
10. Runs `shield:generate --all --panel=admin` when the panel has `MksinePlugin` (in a fresh process if the plugin was registered during this run), then `mks:discover`.
11. Creates the super admin from `--admin-email` / `--admin-password`.
12. Adds sample content with `--seed-demo` (needs Faker).
13. Sets `--locale`, creates the storage link when symlinks are allowed, sets the queue to `database` on shared hosting, and prints the cron line.
14. Writes `storage/mksine/installed.json`.

Without `--migrate`, only the file steps run and the installation is not marked finished. In an interactive terminal the command asks for the admin account, theme, plugins, language and sample content; pass `--no-interaction` to skip the questions.

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
3. Visit `/` and confirm the **active theme** renders (not Laravel's default welcome page). If you still see the welcome view, remove `Route::get('/', …)` from `routes/web.php` (see [Installation §3](../01-installation.md#remove-the-default-homepage-route-from-routeswebphp)).
4. Confirm the navigation contains MKSine sections (for example **Plugins**, **Media**, **Menus**, **Settings**, **Languages**) under appropriate Shield permissions.
5. Confirm MKSine admin styling loaded (sidebar groups, fonts, spacing). If the panel looks like a bare Filament skeleton, run `php artisan optimize:clear` and confirm `/mksine/admin-styles.css` returns CSS. See [Troubleshooting: Admin styles missing](operations/troubleshooting.md#admin-styles-missing-mksine-css).
6. Run the [validation checklist](operations/validation-checklist.md) before considering the install complete.

## Shared hosting: the web installer

For hosts without SSH (cPanel, DirectAdmin), ship a prebuilt zip and install from the browser.

1. Build the zip locally: `php artisan mks:release-archive --layout=split` (see [Release archive](operations/release-archive.md)).
2. Upload and extract it so `mksine/` sits next to `public_html/` (for example `/home/user/mksine` and `/home/user/public_html`).
3. Create an empty database and user in the hosting panel.
4. Open the site. Every request goes to **`/install`** until MKSine is installed.

The wizard (Persian or English) walks through:

| Page | What happens |
|------|--------------|
| Language | Sets the installer language. |
| Requirements | PHP version, extensions, writable paths, disabled functions (`proc_open`, `symlink`) and the request time limit. Warns when the application folder is inside the web root. |
| Database | MySQL, MariaDB, PostgreSQL or SQLite. The connection is tested before `DB_*` is written to `.env`. |
| Site and admin | Site name and address, site language, admin account, theme, plugins and sample content. Writes `APP_NAME`, `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false` and, when the web root is not `public/`, `MKSINE_PUBLIC_PATH`. |
| Install | Runs the same steps as `mksine:install`, one per request, so a 30-second time limit is enough. Migrations run one file at a time. A failed step can be retried and continues where it stopped. |

When it finishes, the installer closes itself (`/install` returns 404) and shows the cron line to add in the panel:

```bash
* * * * * php /home/user/mksine/artisan schedule:run >> /dev/null 2>&1
```

On shared-hosting installs the scheduler also drains the `database` queue every minute (`mksine:shared-hosting-queue-worker`), so no long-running worker is needed. The admin Console page is hidden when `proc_open` is disabled.

**How it boots before installation.** Until `storage/mksine/installed.json` exists, MKSine generates an `APP_KEY` (saved to `.env`), uses file sessions and cache and the `sync` queue, and pins the session cookie name. Sites installed before the installer existed are detected from their tables and get the lock file written automatically.

**Security.** The first browser that opens `/install` owns the installation; other browsers see a "locked" page until the owner has been idle for `mksine.installer.owner_timeout_minutes` (default 360). To reset it, delete `storage/mksine/installer-owner.json`. The admin password is kept only encrypted in the installer session, never in the progress file or logs. The database test is rate limited. Set `MKSINE_WEB_INSTALLER=false` to turn the web installer off entirely.

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
php artisan mks:geo:import --country=IR   # needs outbound HTTPS to GitHub; runs without the queue
```

Then open **System → Settings → Geo** and choose enabled countries and the default country.

See [Global geo system](guides/geo/overview.md) and [Import and migration](guides/geo/import-and-migration.md).

## Next steps

- [Quickstart](02-quickstart.md) — write a working plugin in a few minutes.
- [Concepts: Architecture](concepts/architecture.md) — understand what got registered and why.
