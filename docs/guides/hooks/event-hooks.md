---
title: Event hooks
---

# Event hooks

Event hooks are the **discoverable, database-backed** family. A listener is a class that implements [`MksineListenerInterface`](../../reference/contracts.md#mksinelistenerinterface) and reacts to an event of type `Miran\Mksine\Core\Events\MksineEvent`.

If you only need a closure, use [Runtime registration](runtime-registration.md). If you need a row in `mks_hooks` with an admin toggle and a priority override, you want this page.

## The contract

```php
interface MksineListenerInterface
{
    public function handle(MksineEvent $event): void;
    public function shouldHandle(MksineEvent $event): bool;
    public function shouldQueue(): bool;
    public function priority(): int;
}
```

| Method                       | Purpose                                                                 |
| ---------------------------- | ----------------------------------------------------------------------- |
| `handle($event)`             | Do the work. Return value is ignored.                                   |
| `shouldHandle($event)`       | Cheap pre-check. Return `false` to skip without consuming a slot.       |
| `shouldQueue()`              | Opt this listener into async dispatch (see [Async and queues](async-and-queues.md)). |
| `priority()`                 | Default ordering. Lower = earlier. The DB row in `mks_hooks` may override this. |

## Minimal listener

```php
<?php

declare(strict_types=1);

namespace Acme\MyPlugin\Listeners;

use Illuminate\Support\Facades\Log;
use Miran\Mksine\Core\Events\MksineEvent;
use Miran\Mksine\Core\Hooks\MksineListenerInterface;

final class LogPostCreatedListener implements MksineListenerInterface
{
    public function handle(MksineEvent $event): void
    {
        $postId = $event->context()['post_id'] ?? null;
        $title  = $event->data()->get('title');

        Log::info('Post created', ['post_id' => $postId, 'title' => $title]);
    }

    public function shouldHandle(MksineEvent $event): bool
    {
        return $event->data()->get('status') === 'published';
    }

    public function shouldQueue(): bool
    {
        return false;
    }

    public function priority(): int
    {
        return 20;
    }
}
```

## Registration

You bind a listener to an event name in two steps:

1. **Create the class** under a directory that `mks:discover` will scan (the package’s `Core/Listeners` is scanned automatically; for plugin-owned listeners add the directory to `mksine.hooks.discovery_paths` — see [Discovery paths](discovery-paths.md)).
2. **Register the binding** in your plugin’s `boot()` once, so the registry knows _which event name_ goes to _which listener class_:

   ```php
   public function boot(): void
   {
       Hooks::register('post.created', LogPostCreatedListener::class, 20);
   }
   ```

   The `priority` argument here is the **default** in the registry; the runtime priority is whatever sits in `mks_hooks.priority` for that listener (when present).

> **Why both?** `Hooks::register()` populates the in-memory `HookRegistry` so dispatch knows the mapping. `mks:discover` populates `mks_hooks` so operators can disable or re-prioritise it. They are complementary, not redundant.

## Execution lifecycle (8 phases)

`HookManager::dispatch()` follows the canonical lifecycle in [`HookLifecycle`](../../reference/contracts.md#hooks-and-execution-flow). It is `final` and not extensible.

1. **Dispatch** – caller does `app(HookManager::class)->dispatch($event)`.
2. **Load definitions** – `HookRegistry::getListenersForEvent($name)` returns the in-memory bindings.
3. **Merge runtime state** – `HookStateRepository` reads `mks_hooks` once per request (cached) and emits `is_system`, `is_enabled`, and `priority` overrides.
4. **Sort** – disabled listeners are filtered out (system hooks never are), then sorted by **effective** priority.
5. **Execute synchronous listeners** – for each listener:
   - `shouldQueue()` true → push onto `pendingAsyncListeners`, skip sync execution.
   - `shouldHandle($event)` false → skip.
   - Otherwise call `handle($event)`.
   - If the listener calls `$event->prevent($reason)` (only allowed when `canBePrevented()` returns true), the loop **breaks immediately**.
6. **Stop on prevent** – no further listeners fire; `mutations()` already accumulated still apply.
7. **Dispatch async** – any listener in `pendingAsyncListeners` is handed to `HookAsyncDispatcherInterface` _if and only if_ the event allows async **and** implements `QueueableHookEventInterface` (see [Async and queues](async-and-queues.md)). A `LogicException` is thrown if `allowAsync()` is true but the interface is missing.
8. **Return result** – an `EventResult` value object containing `wasPrevented`, `preventReason`, `mutations`, and `pendingAsyncListeners`.

## Mutating event data

`MksineEvent::updateData($key, $value)` mutates the in-memory bag and records a row in `mutations()`. The caller is responsible for **applying** the mutations to whatever real model triggered the event — `MksineEvent` is a value object, not an Eloquent observer.

```php
public function handle(MksineEvent $event): void
{
    $title = (string) $event->data()->get('title');
    $event->updateData('title', trim($title));
}
```

`updateData()` throws if called on a prevented event. Mutations recorded by listeners that ran _before_ a `prevent()` are still returned in the `EventResult`. **The caller must decide whether to honour them.** Most callers should _not_ apply mutations from a prevented event; treat `wasPrevented = true` as “discard the proposal”.

## System listeners

A row in `mks_hooks` with `is_system = true`:

- **Always runs**, even if `is_enabled = false`.
- Cannot be disabled from the admin UI.
- Should be reserved for cross-cutting invariants the platform _must_ keep (audit logging, mandatory authorisation checks).

Set this flag from a discovery service or migration; never expose it to plugin authors as a casual escape hatch.

## Error handling

`HookDispatcher` does **not** catch exceptions thrown by listeners. A throwing listener:

- Aborts the dispatch loop.
- Surfaces as a normal PHP exception to the caller.
- Does not roll back any mutation already recorded on the event.

If you want soft-failure semantics, **wrap the body of `handle()` in your own try/catch** and decide what to do (log, prevent, swallow). The kernel will not do this for you. See [`ErrorIsolationPolicy`](../../reference/contracts.md#error-isolation-policy) for the contract — the policy class exists, but the dispatcher does not currently apply it. Treat exceptions as fatal.

## Operating-time controls

Once `mks:discover` has populated `mks_hooks`, you can:

- Set `is_enabled = false` to disable a non-system listener.
- Set `priority` to override the default ordering.
- Set `is_system = true` (carefully) to make a listener unstoppable.

Cache invalidation: call `app(HookManager::class)->clearCache()` if you flip rows mid-request (rare; the cache is per-request anyway).

## See also

- [Two hook families](overview-two-families.md)
- [Runtime registration](runtime-registration.md)
- [Async and queues](async-and-queues.md)
- [Discovery paths](discovery-paths.md)
- Reference: [Contracts](../../reference/contracts.md), [Facades and managers](../../reference/facades-and-managers.md)
