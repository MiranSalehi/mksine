<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

/**
 * Abstraction for dispatching hook listeners to an async backend.
 *
 * HookManager depends only on this interface. Laravel Queue (or any other
 * driver) is implemented in a separate layer. Enables testing and swapping.
 */
interface HookAsyncDispatcherInterface
{
    /**
     * Dispatch a listener to run asynchronously with the given event payload.
     *
     * @param  string  $listenerClass  Fully qualified listener class name
     * @param  string  $eventClass  Fully qualified event class name (must implement QueueableHookEventInterface)
     * @param  array{v: int, data: array<string, mixed>, context?: array<string, mixed>}  $payload  Versioned payload from event->toQueuePayload()
     */
    public function dispatchAsync(string $listenerClass, string $eventClass, array $payload): void;
}
