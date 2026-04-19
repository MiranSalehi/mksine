# Hooks contract

MKSine has **two extension families**. Do not document them as one pipeline; do not assume everything syncs to `mks_hooks`.

## 1. Discover + database (`mks_hooks`)

**Command:** `php artisan mks:discover`

**What it syncs:** Class-based listeners implementing:

- `MksineListenerInterface` (event hooks)
- `FormHookListenerInterface` (form hooks; names like `post.form`)
- `TableHookListenerInterface` (table hooks; names like `post.table`)

**Discovery paths (critical):**

1. **Always** scanned: `packages/mksine/src/Core/Listeners` inside the package.
2. **Additionally:** every directory listed in `config('mksine.hooks.discovery_paths')` that exists on disk.

Configure extra paths in `config/mksine.php`:

```php
'discovery_paths' => [
    base_path('app/Hooks/Listeners'),
    base_path(config('mksine.plugins_path').'/my-plugin/src/Listeners'),
],
```

Relative strings are resolved with `base_path()`. Missing paths are skipped with a warning.

**If you omit a plugin’s listener directory here, your class will never sync to `mks_hooks`.** Runtime-only registration is not enough for this family.

**State:** Rows in `mks_hooks` store enabled/priority; system hooks may still run when “disabled” (see package `HookDispatcher` / `is_system`).

## 2. Runtime and template (no `mks_hooks`)

These are **not** populated by `mks:discover`:

| Mechanism | Use case |
|-----------|----------|
| `Hooks::` facades / helpers | Imperative extension in PHP |
| `ResourceHookManager` | Relations / widgets for a resource key |
| `theme_add_action` / `@themeDoAction` | Theme Blade hooks (WordPress-style) |

Register these in plugin `boot()` or service providers. They are **orthogonal** to DB listener sync.

## 3. Filament form/table application

Core resources call `FormHookManager::apply('{name}.form', …)` and `TableHookManager::apply('{name}.table', …)` with stable names (e.g. `post.form`). Plugin-provided **class** hooks must be discovered as in section 1, or you use runtime managers from section 2 depending on the extension point.

## 4. Operational checklist

- After adding listener **classes**: `php artisan mks:discover`
- After changing `discovery_paths`: `php artisan mks:discover`
- If `mks_hooks` table missing: run application migrations first

## Further reading

- Archived deep dive: [archive/README-v1-monolithic.md](archive/README-v1-monolithic.md) (hook lifecycle, queue, prevention).
- ADR: [adr/001-two-hook-families.md](adr/001-two-hook-families.md)
