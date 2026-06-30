---
title: Shield and policies
---

# Shield and policies

MKSine relies on **Laravel Gate policies** for model authorization and on **Filament Shield** (`bezhansalleh/filament-shield`) for generating per-resource permissions and per-page guards. This guide covers how the two work together and what you must do to keep authorization working as you add plugins, resources, and pages.

## How policies are wired

Filament’s default policy auto-discovery only finds classes under `App\Policies\*` for models under `App\Models\*`. Package models live under `Miran\Mksine\Models\*`, so the package wires them explicitly:

```468:486:packages/mksine/src/MksineServiceProvider.php
    protected function registerModelPolicies(): void
    {
        $bindings = [
            Category::class => CategoryPolicy::class,
            Comment::class => CommentPolicy::class,
            Media::class => MediaPolicy::class,
            Menu::class => MenuPolicy::class,
            MenuLocation::class => MenuLocationPolicy::class,
            Page::class => PagePolicy::class,
            Post::class => PostPolicy::class,
            Role::class => RolePolicy::class,
        ];

        foreach ($bindings as $model => $policy) {
            if (class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
    }
```

Two facts worth absorbing:

1. **The expected policy classes live under `App\Policies\*`** in the host app, not in the package. This is what `php artisan shield:generate` produces, so installing Shield and running it once produces all the bindings the package expects.
2. **The binding is conditional on `class_exists`.** If the host hasn’t generated a policy yet, the model is **un-policed** — every operation is allowed (Laravel’s default) until you generate one. This is a footgun on fresh installs that skip Shield setup.

## Bootstrapping Shield

### Prerequisites

1. Filament admin panel exists (`php artisan filament:install --panels` or `make:filament-panel admin`).
2. `MksinePlugin::make()` is registered on that panel **before** permissions are generated.
3. [`mksine:install --migrate`](../../reference/commands.md#mksineinstall) has run (publishes Shield config, permission tables, migrates, and — when the panel is ready — runs `shield:generate --all` and `mks:discover`).

### Minimal path on your app database

```bash
php artisan mksine:install --migrate
php artisan mksine:create-super-admin
```

If you ran `mksine:install --migrate` **before** registering `MksinePlugin`, Shield will not have scanned MKSine resources. Fix:

```bash
php artisan shield:generate --all --panel=admin
```

`mksine:create-super-admin` creates a user, ensures the configured `super_admin` role exists, syncs **all** `permissions` IDs onto that role, and assigns the role.

**Alternatives:**

| Goal | Command |
|------|---------|
| Full interactive Shield setup (config, migrations, optional `shield:install` / `shield:super-admin` prompts) | `php artisan shield:setup` |
| Promote or create super admin on app DB (Shield upstream; creates user only when none exist) | `php artisan shield:super-admin` |
| Wipe an **isolated** `mksine_setup` DB, migrate, one super admin, export SQL/SQLite | `php artisan mksine:fresh-super-admin` |

This produces (after `shield:generate --all`):

- `App\Policies\PostPolicy`, `PagePolicy`, `MediaPolicy`, etc. — the policies the package binds.
- Permission rows in `permissions` (`view_any_post`, `create_post`, …).
- Default roles (`super_admin`, configurable).

Without `shield:generate --all`, `mksine:create-super-admin` and `mksine:fresh-super-admin` still create a user and role, but the role has **no effective permissions** until permission rows exist.

## What `mksine.user_model` and `sync_auth_user_model` do

```php
'user_model' => env('MKS_CMS_USER_MODEL', \App\Models\User::class),
'sync_auth_user_model' => env('MKS_CMS_SYNC_AUTH_USER_MODEL', true),
```

When `sync_auth_user_model = true` (default), the package overrides:

- `auth.providers.users.model`
- `filament-shield.auth_provider_model`

…to whatever `mksine.user_model` resolves to **at boot**. This means installers don’t need to set the FQCN in three places.

If you need to override the user model from a plugin (e.g. a subclass that adds traits or columns), do it in the plugin’s `boot()` after MKSine has booted:

```php
config([
    'auth.providers.users.model'          => \Acme\Accounts\Models\User::class,
    'mksine.user_model'                   => \Acme\Accounts\Models\User::class,
    'filament-shield.auth_provider_model' => \Acme\Accounts\Models\User::class,
]);
```

See [User subclass](user-subclass.md) for the full pattern, including `getMorphClass()` to keep existing Spatie permission rows pointed at the original morph.

## Page-level Shield

Filament pages opt into Shield via the `HasPageShield` trait:

```php
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class Settings extends Page implements HasSchemas, HasActions
{
    use HasPageShield;
}
```

Every page that uses this trait will:

- Generate a permission row keyed by the page class.
- Be visible only to users with that permission.

Package pages already opt in: `Settings`, `Languages`, `ManagePlugins`, `ThemeManager`. After running `shield:generate --all`, you’ll see the matching `page_*` permissions appear.

When you add a Filament page in your plugin (`mks-plugin:make-filament-page`), the scaffolded class includes `HasPageShield` by default. Re-run `shield:generate` after adding new pages.

## Per-resource permissions

Filament Shield discovers Filament `Resource` classes and generates seven permissions per resource: `view_any_…`, `view_…`, `create_…`, `update_…`, `restore_…`, `delete_…`, `force_delete_…`.

Package resources (`Posts`, `Pages`, `Categories`, `Comments`, `Tags`, `Media`, `Menus`, `MenuLocations`, `Users`, `Roles`) generate these on `shield:generate --all`.

Plugin resources discovered by the package (under `{plugin_root}/{plugin_id}/src/Filament/Resources/`) are also picked up by Shield’s scan once they’re registered with the panel. **Re-run `shield:generate --resource=…` (or `--all`) after enabling a new plugin** so its permissions exist before you assign roles.

## Checking permissions in your code

Filament resources/pages handle authorization automatically through the policies and the Shield trait. Outside of them, use Laravel’s `Gate`:

```php
if (! Gate::allows('update', $post)) {
    abort(403);
}

// Or with the Spatie permission trait the package depends on:
if (! $user->hasPermissionTo('update_post')) {
    abort(403);
}
```

Permission names follow Shield’s convention: `{action}_{resource_singular_snake}`, e.g. `delete_menu_location`. Use them directly in checks; the policies the package wires reduce to those calls.

## Adding a policy for a custom plugin model

If your plugin ships a model and a Filament resource for it, generate the policy and the permissions:

```bash
php artisan make:policy AppointmentPolicy --model=Acme\\Booking\\Models\\Appointment
php artisan shield:generate --resource=AppointmentResource
```

Then either let Filament auto-discover (works if the model is `App\Models\Appointment`) or bind explicitly in your plugin’s `boot()`:

```php
use Illuminate\Support\Facades\Gate;
use Acme\Booking\Models\Appointment;
use App\Policies\AppointmentPolicy;

public function boot(): void
{
    if (class_exists(AppointmentPolicy::class)) {
        Gate::policy(Appointment::class, AppointmentPolicy::class);
    }
}
```

The `class_exists` guard is intentional — you do not want your plugin’s `boot()` crashing because Shield hasn’t been generated yet on a fresh install.

## Honest limitations and gotchas

- **Un-policed models are wide open.** If `shield:generate --all` never ran (or ran before `MksinePlugin` was registered), the package’s `Gate::policy()` calls become no-ops and Filament resources fall back to "anyone authenticated can do anything". Register `MksinePlugin` first, then run `mksine:install --migrate` or `shield:generate --all --panel=admin`.
- **Plugin permissions are not auto-seeded.** Activating a plugin does not generate its permissions. You must re-run `shield:generate --resource=…` after enabling.
- **Custom abilities are not granted by `super_admin` automatically** unless `super_admin` is registered through Shield’s super admin handler (the default). Verify with `Bouncer`/`Spatie` — Shield uses Spatie under the hood — that the super admin role grants `*` via the package’s gate-before hook.
- **No per-tenant isolation by default.** If you add multi-tenancy later, Shield permissions are global; you’ll need to combine them with team scoping (Spatie supports this) and re-think `MKSine`’s assumption of a single user/role table.
- **Replacing `user_model` after data exists.** If existing `model_has_roles.model_type` rows reference the old user FQCN, override `getMorphClass()` on your subclass to return the original class string. Otherwise role assignments orphan silently.
- **Shield’s middleware is not applied automatically to the public theme.** Public theme pages use no policy by default — render with caution if you expose admin-ish functionality there.

## See also

- [User subclass](user-subclass.md)
- [`config/mksine.php` auth keys](../../reference/configuration.md)
- [Filament Shield documentation](https://filamentphp.com/plugins/bezhansalleh-shield) for the upstream package API.
