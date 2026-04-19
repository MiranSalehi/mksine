# Security and auth (plugins)

## Principle: do not couple to `App\Models\User` blindly

Plugins that need extra columns, casts, or auth behavior should **avoid** patching the host `User` model directly. That creates tight coupling and upgrade pain.

### Recommended pattern

1. Define `YourPlugin\Models\User extends App\Models\User` (or the host’s published user class).
2. Put plugin-only `$fillable`, casts, relations, and behavior on the subclass.
3. In the plugin `boot()` (or dedicated registrar), set **all** of these to the same FQCN:

   - `config('auth.providers.users.model')`
   - `config('mksine.user_model')`
   - `config('filament-shield.auth_provider_model')`

MKSine runs `syncAuthUserModelWithMksineConfig()` early; **plugins must apply the final model** if they replace the user class.

### Filament access

The resolved user class must satisfy Filament + Shield contracts used by the app (e.g. `FilamentUser`, `HasPanelShield` or equivalent). Otherwise panel login may loop or 403.

### Spatie morph types

If `model_has_roles` (or other morph tables) already store `App\Models\User` as `model_type`, the subclass may need to override `getMorphClass()` to return the **application** class string so existing rows still match. **Do not** rewrite production `model_type` without a migration strategy.

### Policies and permissions

Use Filament Shield (or app policies) like core resources. New plugin resources need permissions generated/assigned like any other resource.

## Further reading

- ADR: [adr/003-plugin-user-subclass.md](adr/003-plugin-user-subclass.md)
