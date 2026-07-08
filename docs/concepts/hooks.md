---
title: Hooks
description: How extension points work in MKSine.
order: 2
---

# Hooks

The hook system is how MKSine lets you extend behavior without forking the package or another plugin. There are two families. Read both before deciding how to wire your extension.

## Two families

### Discovery hooks

Class-based listeners scanned by `php artisan mks:discover`. The scanner reads canonical paths (the package's own `Core/Listeners/` plus `config('mksine.hooks.discovery_paths')`), inspects each class for the relevant interface, and writes a row into `mks_hooks` per discovered listener.

The DB row is not just bookkeeping — it lets the admin enable, disable, and reorder listeners without code changes. System-flagged listeners always run; user-flagged ones honour the toggle.

Use discovery hooks for:

- Long-lived behavior the user might want to disable.
- Listeners shipped by plugins that should be visible in the admin's Hooks page.
- Anything that needs admin-controlled priority overrides.

Trade-offs:

- Requires re-running `mks:discover` after code changes.
- Adds a row in the DB (negligible at any realistic scale).

### Runtime hooks

Closures or class callbacks registered in a plugin's `boot()` via the `Hooks::` static facade. Lives in memory only; no DB row, no admin toggle, no discovery step.

Use runtime hooks for:

- Conditional registration (`if (feature_x_enabled())`).
- Hooks tied to a plugin's identity (uninstall the plugin, the hook is gone automatically).
- Resource relations / widgets / page header actions — these are runtime-only by design.

Trade-offs:

- Invisible in the admin.
- No exception isolation for resource/page hooks (the `FormHookManager` does catch exceptions; the others don't).
- Re-registered on every request boot.

[Two families overview](../guides/hooks/overview-two-families.md) lays out the decision tree.

## Five kinds of extension surface

| Surface                          | Discovery? | Runtime?       | Catches exceptions?      |
| -------------------------------- | ---------- | -------------- | ------------------------ |
| Event listener (`MksineEvent`)   | Yes        | Yes (closures) | No (let them propagate)  |
| Form extension                   | Yes        | Yes            | Yes (`FormHookManager`)  |
| Table extension                  | Yes        | Yes            | No                       |
| Resource relation / widget       | No         | Yes only       | No                       |
| Page header action               | No         | Yes only       | No                       |
| Runtime filter (`Hooks::filter`) | No         | Yes only       | No                       |

The asymmetry around exception catching is intentional but you should design for it. See [Form hooks](../guides/hooks/form-hooks.md) and [Table hooks](../guides/hooks/table-hooks.md).

Notable runtime filters:

- `frontend_admin_bar.items` — storefront admin bar menu (links and dropdowns). See [Frontend admin bar](../guides/storefront/frontend-admin-bar.md).
- `mksine.content.before_shortcodes` / `mksine.content.after_shortcodes` — wrap shortcode parsing. See [Shortcodes](../guides/content/shortcodes.md).
- Plugin-specific filters (e.g. `ecom.checkout.available_payment_methods`) — documented in each plugin's developer API.

## How a hook fires (event hooks)

1. A package or plugin dispatches an event extending `MksineEvent`. The event carries a name and a data bag.
2. The `HookManager` resolves all listeners registered for that name (from in-memory registry + DB-backed runtime overrides).
3. Listeners run in priority order. Lower priorities run first.
4. Each listener's `shouldHandle($event)` is consulted; it can short-circuit per-listener.
5. The dispatcher decides sync vs async based on the four conditions described in [Async and queues](../guides/hooks/async-and-queues.md).
6. The data bag may be mutated in place (sync only); `cancel()` advisory.

There is no return value. Listeners that need to "answer" the event should mutate the data bag.

## What you cannot do (yet)

- Wildcards. `Hooks::on('post.*', ...)` is not supported.
- Discovery-backed filter chains. Runtime filters (`Hooks::addFilter`) exist but are not synced to `mks_hooks`.
- Async **per** form/table extension. Form/table extensions are always sync.
- Cancel a running listener mid-flight. `cancel()` is documented but not enforced.

## Performance posture

Hooks run on every dispatch. The dispatcher does not currently log slow listeners (`mksine.hooks.log_slow_hooks` is configured but unimplemented — see [Slow-hook logging](../guides/hooks/slow-hook-logging.md)). You are responsible for keeping listeners fast and idempotent. A listener that adds 200 ms to every request will silently degrade your p95.

For anything heavier than serializing the event and queuing work, implement `QueueableHookEventInterface` and let the listener queue itself.

## See also

- [Two families overview](../guides/hooks/overview-two-families.md)
- [Event hooks](../guides/hooks/event-hooks.md)
- [Form hooks](../guides/hooks/form-hooks.md), [Table hooks](../guides/hooks/table-hooks.md), [Resource hooks](../guides/hooks/resource-hooks.md), [Page header hooks](../guides/hooks/page-header-hooks.md)
- [Runtime registration](../guides/hooks/runtime-registration.md)
- [Async and queues](../guides/hooks/async-and-queues.md)
- [Discovery paths](../guides/hooks/discovery-paths.md)
- [Frontend admin bar](../guides/storefront/frontend-admin-bar.md)
- [Shortcodes](../guides/content/shortcodes.md)
- ADR: [Two hook families](../adr/001-two-hook-families.md)
