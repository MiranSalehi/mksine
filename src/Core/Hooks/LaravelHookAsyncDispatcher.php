<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

use Miran\Mksine\Core\Hooks\Jobs\ProcessHookListenerJob;

/**
 * Laravel Queue implementation of HookAsyncDispatcherInterface.
 *
 * Dispatches one job per listener. No direct dependency from HookManager
 * on Laravel Queue; HookManager depends only on the interface.
 */
final class LaravelHookAsyncDispatcher implements HookAsyncDispatcherInterface
{
    public function dispatchAsync(string $listenerClass, string $eventClass, array $payload): void
    {
        ProcessHookListenerJob::dispatch($eventClass, $payload, $listenerClass);
    }
}
