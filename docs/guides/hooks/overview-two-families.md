---
title: Hooks: the two families
---

# Hooks: the two families

MKSine ships a **single dispatch kernel** but exposes **two** complementary registration models. They are not interchangeable; pick deliberately.

| Family                | Source of truth        | Registered when         | Catalogued in DB? | Toggleable in admin UI?     | Best for                                |
| --------------------- | ---------------------- | ----------------------- | ----------------- | --------------------------- | --------------------------------------- |
| **Discovery hooks**   | A class on disk        | `mks:discover`          | Yes (`mks_hooks`) | Yes (enable/disable + priority override) | Stable, long-lived behaviour shipped by a plugin |
| **Runtime hooks**     | A closure in PHP       | Boot of a plugin / SP   | No                | No                          | Conditional wiring, prototyping, host-app glue   |

Both families land in the same managers (`HookManager`, `FormHookManager`, `TableHookManager`, `ResourceHookManager`, `PageHookManager`). The difference is **how they get there** and **what an operator can do about them at runtime**.

## Mental model

A request walks through this hierarchy:

```
HookManager::dispatch(MksineEvent)                ← event hooks
   └ executes MksineListenerInterface listeners

FormHookManager::apply('post.form', $schema)      ← form hooks (Filament)
   └ runs callables registered for 'post.form'

TableHookManager::apply('post.table', $table)     ← table hooks (Filament)
   └ runs callables registered for 'post.table'

ResourceHookManager::applyRelations|applyWidgets  ← resource hooks
   └ runs callables registered for 'post.resource'

PageHookManager::applyHeaderActions               ← page header action hooks
   └ runs callables registered for 'post.list', 'post.edit', …
```

There is no parallel database catalogue for `FormHookManager`, `TableHookManager`, `ResourceHookManager`, or `PageHookManager` — only **event hooks** are persisted. Form/table (and form **slot**) hooks can be _shipped_ as discoverable classes (so `mks:discover` records them in the schema for inventory: `form`, `form_slot`, `table`), but their execution always goes through the in-memory manager.

## Discovery hooks at a glance

- Implement [`MksineListenerInterface`](../../reference/contracts.md#mksinelistenerinterface), [`FormHookListenerInterface`](../../reference/contracts.md#formhooklistenerinterface), or [`TableHookListenerInterface`](../../reference/contracts.md#tablehooklistenerinterface).
- Place the class anywhere PSR-4 can find it.
- Add the **directory root** to `config('mksine.hooks.discovery_paths')` (the package’s own `Core/Listeners` is always scanned).
- Run `php artisan mks:discover`. The class is recorded in `mks_hooks`; the host can flip `is_enabled` or override `priority` from the admin or directly in the table.
- Reads are deterministic: `HookManager` filters `is_enabled = false` listeners _unless_ they are flagged `is_system = true` ([`HookDispatcher::dispatch`](../../reference/contracts.md#hookasyncdispatcherinterface)).

See [Discovery paths](discovery-paths.md) for the lookup rules and [Event hooks](event-hooks.md) for the lifecycle phases.

## Runtime hooks at a glance

- Call `Hooks::register('post.creating', MyListener::class, 10)` for event listeners, or `Hooks::extendForm('post.form', fn ($schema) => …)` for form/table extensions.
- Registration must run on **every request** — typically inside a plugin’s `boot()` method or a service provider’s `boot()`.
- Nothing is persisted. There is no `is_enabled` row, no admin toggle, no priority override surface.
- This is the right tool when behaviour depends on host configuration, on the active panel, or on values not known at deploy time.

See [Runtime registration](runtime-registration.md) for patterns and pitfalls.

## When to choose which

| Need                                                                                 | Use                |
| ------------------------------------------------------------------------------------ | ------------------ |
| Plugin user must be able to toggle a behaviour from the admin                        | Discovery (event)  |
| Plugin needs a deterministic priority override per environment                       | Discovery (event)  |
| Conditional wiring (only when feature flag X is on, only on the customer panel, …)   | Runtime            |
| Quickly iterate on a callback during development                                     | Runtime, then promote to a class once stable |
| Field/column added to a form/table from a class that ships with a plugin             | Discovery (form/table) |
| Field/column added with logic that depends on the request                            | Runtime            |
| Resource relations / widgets / page header actions                                   | Runtime (`Hooks::extendResource…`, `Hooks::extendPageHeaderActions`) — there is **no** discoverable equivalent |
| Storefront admin bar menu items                                                      | Runtime filter `frontend_admin_bar.items` — see [Frontend admin bar](../storefront/frontend-admin-bar.md) |
| Async behaviour after a record is created/updated                                    | Discovery + queueable event (see [Async and queues](async-and-queues.md)) |

## Honest limits

The discovery vs runtime split is intentional, but it has real consequences you must own:

- **Runtime hooks have no operational surface.** There is no UI to disable them, no DB record, and no audit trail. If a plugin ships a runtime listener that misbehaves in production, the only way to stop it is to deploy a new release of that plugin. Treat runtime hooks as **code**, not as **configuration**.
- **Discovery hooks are not free.** Adding a directory to `discovery_paths` makes `mks:discover` walk the filesystem and reflect every PHP file under it. Keep the path narrow (e.g. `src/Hooks/Listeners`), not the whole `src/`.
- **`is_system` is a one-way street.** A row marked `is_system = true` in `mks_hooks` will execute even when `is_enabled = false`. Use it for invariants the platform must keep (audit logging, security checks). Never use it for optional behaviour.

## See also

- [Event hooks](event-hooks.md)
- [Form hooks](form-hooks.md)
- [Table hooks](table-hooks.md)
- [Resource hooks](resource-hooks.md)
- [Page header hooks](page-header-hooks.md)
- [Runtime registration](runtime-registration.md)
- [Async and queues](async-and-queues.md)
- [Discovery paths](discovery-paths.md)
- [Slow-hook logging](slow-hook-logging.md)
- [ADR 001 – Two hook families](../../adr/001-two-hook-families.md)
