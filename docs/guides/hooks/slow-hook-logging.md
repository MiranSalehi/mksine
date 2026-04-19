---
title: Slow-hook logging
---

# Slow-hook logging

> **Status: configured but not implemented in the current `HookDispatcher`.** This page documents the intended contract and the workaround you should use today. Treat it as a roadmap item, not a feature you can rely on out of the box.

## What `config('mksine.hooks.log_slow_hooks')` is supposed to do

The package config ships with:

```php
'log_slow_hooks'      => env('MKS_CMS_LOG_SLOW_HOOKS', true),
'slow_hook_threshold' => env('MKS_CMS_SLOW_HOOK_THRESHOLD', 100),
```

The intent — visible in the config comments and the `MksineServiceProvider` registration — is that the dispatcher will time each listener and emit a `Log::warning(…)` entry when an execution exceeds `slow_hook_threshold` (in milliseconds).

## What the kernel actually does today

`HookDispatcher::dispatch()` does **not** read `mksine.hooks.log_slow_hooks` or `slow_hook_threshold`. There is no instrumentation around the `$listener->handle($event)` call. Setting these env vars has **no effect**.

You can verify this in `packages/mksine/src/Core/Hooks/HookDispatcher.php` — the dispatch loop has no `microtime()` call, no logger call, and no reference to the slow-hook config keys.

This is a real gap. Until it is closed, the operations docs that reference these knobs (e.g. `operations/troubleshooting.md`, `operations/validation-checklist.md`) describe a contract the kernel does not yet implement. Plan to either (a) implement the instrumentation in the dispatcher, or (b) remove the operations references.

## Workaround until the kernel ships this

Wrap the body of `handle()` in your own timing logic, in any listener you want to monitor:

```php
<?php

declare(strict_types=1);

namespace Acme\MyPlugin\Listeners;

use Illuminate\Support\Facades\Log;
use Miran\Mksine\Core\Events\MksineEvent;
use Miran\Mksine\Core\Hooks\MksineListenerInterface;

abstract class TimedListener implements MksineListenerInterface
{
    public function handle(MksineEvent $event): void
    {
        $start = hrtime(true);

        try {
            $this->doHandle($event);
        } finally {
            $elapsedMs = (hrtime(true) - $start) / 1_000_000;
            $threshold = (int) config('mksine.hooks.slow_hook_threshold', 100);

            if ($elapsedMs > $threshold && config('mksine.hooks.log_slow_hooks', true)) {
                Log::warning('mksine.slow_hook', [
                    'listener' => static::class,
                    'event'    => $event->name(),
                    'ms'       => round($elapsedMs, 2),
                    'threshold_ms' => $threshold,
                ]);
            }
        }
    }

    abstract protected function doHandle(MksineEvent $event): void;

    public function shouldHandle(MksineEvent $event): bool { return true; }
    public function shouldQueue(): bool { return false; }
    public function priority(): int { return 0; }
}
```

Concrete listeners then extend `TimedListener` and implement `doHandle()`.

This honours the configured `slow_hook_threshold` and `log_slow_hooks` keys, so when the kernel eventually wires them up natively your monitoring expectations stay consistent.

## Recommended thresholds

- **Synchronous web request hooks**: 50–100 ms. Anything above is a real problem; you’re shipping latency to the request thread.
- **Console / artisan hooks**: 500 ms.
- **Queueable listeners** (`shouldQueue() === true`): instrument inside the listener, but tune to your worker SLA — slow async work is expected.

## Future direction

The right fix is in the dispatcher itself. A reasonable shape:

1. Read `mksine.hooks.log_slow_hooks` and `slow_hook_threshold` once per dispatch call.
2. Around each `$listener->handle($event)` call, capture `hrtime(true)` before/after.
3. Emit `Log::warning(…)` when the elapsed exceeds the threshold, with structured fields (`listener`, `event`, `ms`, `prevented`).
4. Add a unit test that asserts the warning fires.

Until that lands, **the workaround above is the contract**.

## See also

- [Event hooks](event-hooks.md)
- Reference: [`hooks.log_slow_hooks` and `hooks.slow_hook_threshold`](../../reference/configuration.md#hooks)
