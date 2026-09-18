---
title: Slow-hook logging
---

# Slow-hook logging

`HookDispatcher` times each synchronous listener with `hrtime(true)`.

When `config('mksine.hooks.log_slow_hooks')` is true (default) and the listener exceeds `slow_hook_threshold` (default 100 ms), it writes `Log::warning('mksine.slow_hook', …)` with `listener`, `event`, `ms`, `threshold_ms`, and `prevented`.

Queued listeners are not timed; they are reported as `queued: true` on the Laravel event `Miran\Mksine\Core\Events\HookListenerExecuted` (no event payload).

## Recommended thresholds

- **Synchronous web request hooks**: 50–100 ms.
- **Console / artisan hooks**: 500 ms.
- **Queueable listeners**: instrument inside the job if you need worker SLAs.

## See also

- [Event hooks](event-hooks.md)
- Reference: [`hooks.log_slow_hooks` and `hooks.slow_hook_threshold`](../../reference/configuration.md#hooks)
