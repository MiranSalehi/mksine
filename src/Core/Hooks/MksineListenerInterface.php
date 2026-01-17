<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

use Miran\Mksine\Core\Events\MksineEvent;

/**
 * Interface that all event listeners must implement.
 */
interface MksineListenerInterface
{
    /**
     * Handle the event.
     */
    public function handle(MksineEvent $event): void;

    /**
     * Determine if this listener should handle the given event.
     * Useful for conditional execution based on event data or context.
     */
    public function shouldHandle(MksineEvent $event): bool;

    /**
     * Determine if this listener should be queued for async execution.
     */
    public function shouldQueue(): bool;

    /**
     * Get the priority of this listener.
     * Lower numbers execute first.
     */
    public function priority(): int;
}
