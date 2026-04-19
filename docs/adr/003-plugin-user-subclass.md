# ADR 003: Plugin user subclass instead of patching host User

## Context

Plugins often need extra user fields or behavior. Editing `App\Models\User` from a plugin couples the host app to the plugin and complicates updates.

## Decision

Prefer **`Plugin\Models\User extends HostUser`** with configuration wiring in plugin `boot()` for:

- `auth.providers.users.model`
- `mksine.user_model`
- `filament-shield.auth_provider_model`

Handle Spatie morph `model_type` deliberately (often `getMorphClass()`), not ad hoc data fixes.

## Consequences

- More setup code in complex plugins (wiring in `boot()` per [40-security-auth.md](../40-security-auth.md)).
- Correctness of Shield + Filament contracts remains the plugin author’s responsibility.

## References

- `MksineServiceProvider::syncAuthUserModelWithMksineConfig`
- [40-security-auth.md](../40-security-auth.md)
