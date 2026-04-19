---
title: Async hooks and queues
---

# Async hooks and queues

A listener can opt into asynchronous execution. The kernel ships a Laravel queue dispatcher (`LaravelHookAsyncDispatcher`) but the abstraction is `HookAsyncDispatcherInterface`, so any backend can be wired in.

This page is a contract document, not a “you should always queue” recommendation. The rules are strict — read them before flipping `shouldQueue()` to `true`.

## The four conditions for async dispatch

A listener runs asynchronously **if and only if all four are true**:

1. The listener’s `shouldQueue()` returns `true`.
2. The event class returns `true` from `allowAsync()` (defaults to `false`; override the `protected` method on the event subclass).
3. `config('mksine.hooks.queue.enabled')` is `true` (the default; togglable via `MKS_CMS_HOOKS_QUEUE_ENABLED`).
4. The event class implements `Miran\Mksine\Core\Events\QueueableHookEventInterface`.

Miss any one and the listener runs **synchronously** during dispatch — _silently_ for conditions 1 and 3, and with a hard `LogicException` for condition 4 (so you can never accidentally queue with a non-serializable event).

## The queueable event contract

```php
interface QueueableHookEventInterface
{
    /** @return array{v: int, data: array<string, mixed>, context?: array<string, mixed>} */
    public function toQueuePayload(): array;

    public static function fromQueuePayload(array $payload): static;
}
```

Rules the kernel enforces:

- The payload **must** include an integer `v` key. Use it for explicit version drift handling between the producer (web request) and the consumer (worker process).
- The payload should contain **only primitives and identifiers** — model IDs, primary keys, scalars. **No Eloquent models, no closures, no resources.** The worker rebuilds the event via `fromQueuePayload()`; if you embedded a model, you have already shipped a stale snapshot to the queue.
- `fromQueuePayload()` is responsible for re-fetching anything it needs (`Post::findOrFail($payload['data']['post_id'])` etc.).

If `allowAsync()` returns `true` and the event does not implement this interface, `HookManager::dispatch()` throws:

```
Event "post.created" returns allowAsync() === true but does not implement
Miran\Mksine\Core\Events\QueueableHookEventInterface. Async events must implement
toQueuePayload() and fromQueuePayload().
```

This is intentional: silent fallback to synchronous would leak production load onto request threads.

## Minimal queueable event

```php
<?php

declare(strict_types=1);

namespace Acme\MyPlugin\Events;

use Miran\Mksine\Core\Events\MksineEvent;
use Miran\Mksine\Core\Events\QueueableHookEventInterface;

final class PostPublishedEvent extends MksineEvent implements QueueableHookEventInterface
{
    public function name(): string
    {
        return 'post.published';
    }

    public function canBePrevented(): bool
    {
        return false;
    }

    protected function allowAsync(): bool
    {
        return true;
    }

    public function toQueuePayload(): array
    {
        return [
            'v'       => 1,
            'data'    => $this->allData(),
            'context' => $this->context(),
        ];
    }

    public static function fromQueuePayload(array $payload): static
    {
        return new self($payload['data'], $payload['context'] ?? []);
    }
}
```

## Minimal queueable listener

```php
public function shouldQueue(): bool
{
    return true;
}
```

The listener class itself implements no extra interface. `ProcessHookListenerJob` resolves it from the container, calls `shouldHandle($event)` again on the worker side, and then `handle($event)`. **`shouldHandle()` is re-evaluated on the worker** — design it to be cheap and side-effect free.

## How dispatch flows for a queueable listener

1. `HookManager::dispatch($event)` runs synchronous listeners.
2. Listeners with `shouldQueue() === true` are not executed; they accumulate in `pendingAsyncListeners`.
3. After sync execution, `dispatchPendingAsyncListeners()` validates the four conditions above, then for each pending listener calls `HookAsyncDispatcherInterface::dispatchAsync($listenerClass, $eventClass, $payload)`.
4. The default `LaravelHookAsyncDispatcher` dispatches a `ProcessHookListenerJob` per listener.
5. The worker rebuilds the event via `eventClass::fromQueuePayload($payload)` and runs the listener.

There is **one job per listener**, not one job per event. This isolates failures (a failing listener does not poison its peers) at the cost of repeated payload deserialization. For high-fanout events, consider grouping listeners into one composite listener if dispatch overhead matters.

## Configuration

```php
// config/mksine.php
'hooks' => [
    'queue' => [
        'enabled'    => env('MKS_CMS_HOOKS_QUEUE_ENABLED', true),
        'connection' => env('MKS_CMS_HOOKS_QUEUE_CONNECTION'),  // null → Laravel default
        'queue'      => env('MKS_CMS_HOOKS_QUEUE_NAME'),        // null → default queue name
        'tries'      => env('MKS_CMS_HOOKS_QUEUE_TRIES', 3),
        'backoff'    => env('MKS_CMS_HOOKS_QUEUE_BACKOFF', 60),
        'timeout'    => env('MKS_CMS_HOOKS_QUEUE_TIMEOUT', 120),
    ],
],
```

`ProcessHookListenerJob` reads this config in its constructor (i.e. **on dispatch**, not on execution). Changing the config and restarting the queue worker is enough — no Redis flush, no migration.

`MksineServiceProvider` only binds `HookAsyncDispatcherInterface` when `queue.enabled` is `true`. Setting it to `false` makes `HookManager::$asyncDispatcher` null, in which case `pendingAsyncListeners` are quietly discarded after the validation throws would have been raised. **Operationally, that means turning the queue off during an incident silently drops `shouldQueue()` listeners.** That may or may not be what you want; document it in your runbook.

## Failure handling

- `tries`, `backoff`, and `timeout` are forwarded to Laravel’s `Queueable` trait. Standard Laravel queue behaviour applies — failed jobs land in `failed_jobs`.
- `ProcessHookListenerJob::failed()` writes a structured `error` log entry with `event_class`, `listener_class`, `payload`, and exception details.
- The kernel does **not** automatically retry the synchronous portion of dispatch on async failure. The original request has already returned by then.

## Local development

For local development you almost certainly want to keep `queue.enabled = true` and run the worker:

```bash
php artisan queue:work --queue=$(echo "${MKS_CMS_HOOKS_QUEUE_NAME:-default}")
```

Or, if you intentionally want everything synchronous in dev, set `MKS_CMS_HOOKS_QUEUE_ENABLED=false`. Be aware that this masks performance problems that will appear in production.

## Pitfalls (read before shipping)

- **Long-running jobs.** A listener that hits an external API for 10 seconds will hold a worker slot for 10 seconds. Tune `timeout` and your worker fleet accordingly.
- **Idempotency.** Queue retries can re-execute your listener. Make `handle()` idempotent (use a unique constraint, an `if (already_done) { return; }` guard, or an explicit deduplication table).
- **Payload size.** Don’t shove an entire eloquent model or a multi-MB blob into `toQueuePayload()`. Keep it small.
- **Version drift.** When you change the `data` shape, bump `v` and have the listener (or `fromQueuePayload`) handle both shapes for at least one release.
- **Cross-tenant data.** If your job needs the active tenant/team, include the identifier in the payload and resolve it on the worker. Do **not** rely on `auth()->user()` inside `handle()`.

## See also

- [Event hooks](event-hooks.md)
- Reference: [`QueueableHookEventInterface`](../../reference/contracts.md#queueablehookeventinterface), [`HookAsyncDispatcherInterface`](../../reference/contracts.md#hookasyncdispatcherinterface), [Configuration](../../reference/configuration.md)
