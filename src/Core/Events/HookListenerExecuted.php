<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Events;

/**
 * Fired after a discovered event listener is queued or finishes handling.
 * Does not include event payload (inspectors must not log secrets by default).
 */
final readonly class HookListenerExecuted
{
    public function __construct(
        public string $eventName,
        public string $listenerClass,
        public float $elapsedMs,
        public bool $queued,
        public bool $prevented,
        public bool $isSystem,
    ) {}
}
