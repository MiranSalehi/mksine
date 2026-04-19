---
title: User subclass for plugins
description: How a plugin can extend the host User model without breaking auth, Filament, or Shield.
order: 0
---

# User subclass (plugins)

## Principle: do not patch the host `App\Models\User` blindly

Plugins that need extra columns, casts, or auth behavior should **avoid** modifying the host `User` model directly. Patching the host class creates tight coupling and upgrade pain across host apps that already shipped their own user logic.

## Recommended pattern

1. Define `YourPlugin\Models\User extends App\Models\User` (or the host’s published user class).
2. Put plugin-only `$fillable`, casts, relations, and behavior on the **subclass**.
3. In the plugin `boot()` (or a dedicated registrar invoked from `boot()`), set **all** of these to the same FQCN:

   - `config('auth.providers.users.model')`
   - `config('mksine.user_model')`
   - `config('filament-shield.auth_provider_model')`

MKSine runs `syncAuthUserModelWithMksineConfig()` early when `mksine.sync_auth_user_model = true`. Plugins that replace the user class must apply the **final** model after that sync runs (typically inside `boot()` after MKSine has booted).

## Filament access

The resolved user class must satisfy the Filament + Shield contracts used by your panel (for example `Filament\Models\Contracts\FilamentUser`, `Filament\Models\Contracts\HasName`, and Shield’s `HasPanelShield` trait or its equivalent). If those are missing, panel login may loop or 403.

## Spatie morph types

If `model_has_roles` (or other morph tables) already store `App\Models\User` as `model_type`, a subclass may need to override `getMorphClass()` to return the **application** class string so existing rows still match:

```php
public function getMorphClass(): string
{
    return \App\Models\User::class;
}
```

Do **not** rewrite production `model_type` rows without a migration strategy. Pick a side: alias morph map entries (`Relation::enforceMorphMap([...])`) or override `getMorphClass()`, then keep it consistent across the codebase.

## Policies and permissions

Use Filament Shield (or your application policies) the same way you would for core resources. New plugin resources need permissions generated and assigned per role just like any other resource.

## End-to-end example

Inside a plugin called `accounts`:

```php
// {plugin_root}/accounts/src/Models/User.php
namespace Plugins\Accounts\Models;

class User extends \App\Models\User
{
    protected $fillable = [
        // existing host fillables PLUS plugin-only columns
        ...parent::$fillable ?? [],
        'preferred_locale',
        'plan_tier',
    ];

    public function getMorphClass(): string
    {
        return \App\Models\User::class;
    }
}
```

```php
// {plugin_root}/accounts/src/AccountsPlugin.php
public function boot(): void
{
    config([
        'auth.providers.users.model'           => \Plugins\Accounts\Models\User::class,
        'mksine.user_model'                    => \Plugins\Accounts\Models\User::class,
        'filament-shield.auth_provider_model'  => \Plugins\Accounts\Models\User::class,
    ]);
}
```

## Edge cases

- **Multiple plugins replace the user class.** Only one wins; whichever boots last overwrites the previous values silently. Pick **one** owner plugin and document it in your application; do not let two plugins fight over the user class.
- **Cached config.** Running `php artisan config:cache` snapshots the original `auth.providers.users.model`. Plugin `boot()` mutates **runtime** config, so cached config still resolves to the original class for code paths that use the snapshot. Audit any code that reads the cached config object directly.
- **Policy auto-resolution.** Laravel matches policies by class name. A subclass without a registered policy will fall back to the parent’s policy if you registered with `Gate::policy(App\Models\User::class, ...)`; verify behavior with `php artisan permission:show` if you use Spatie permission.

## See also

- ADR: [adr/003-plugin-user-subclass.md](../../adr/003-plugin-user-subclass.md)
- [Shield and policies](shield-and-policies.md)
- [Configuration](../../reference/configuration.md) — `mksine.user_model`, `mksine.sync_auth_user_model`
