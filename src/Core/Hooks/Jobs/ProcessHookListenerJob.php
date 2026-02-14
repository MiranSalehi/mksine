<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Miran\Mksine\Core\Events\MksineEvent;
use Miran\Mksine\Core\Hooks\MksineListenerInterface;
use Throwable;

/**
 * Job that runs a single hook listener with a rebuilt event from queue payload.
 *
 * Event is rebuilt via eventClass::fromQueuePayload(payload). No direct constructor use.
 * Listener is resolved from container (must be stateless or container-resolvable).
 */
class ProcessHookListenerJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /** @var int|null */
    public $tries;

    /** @var int|null */
    public $backoff;

    /** @var int|null */
    public $timeout;

    /**
     * @param  string  $eventClass  Fully qualified event class (must implement QueueableHookEventInterface)
     * @param  array{v: int, data: array<string, mixed>, context?: array<string, mixed>}  $payload  Versioned payload
     * @param  string  $listenerClass  Fully qualified listener class
     */
    public function __construct(
        private readonly string $eventClass,
        private readonly array $payload,
        private readonly string $listenerClass
    ) {
        $queueConfig = config('mksine.hooks.queue', []);

        $this->connection = $queueConfig['connection'] ?? null;
        $this->queue = $queueConfig['queue'] ?? null;

        if (isset($queueConfig['tries'])) {
            $this->tries = (int) $queueConfig['tries'];
        }
        if (isset($queueConfig['backoff'])) {
            $this->backoff = (int) $queueConfig['backoff'];
        }
        if (isset($queueConfig['timeout'])) {
            $this->timeout = (int) $queueConfig['timeout'];
        }
    }

    public function handle(): void
    {
        if (! isset($this->payload['v']) || ! is_int($this->payload['v'])) {
            throw new \InvalidArgumentException(
                'Hook queue payload must contain integer version key "v". ' .
                'Event: ' . $this->eventClass . ', Listener: ' . $this->listenerClass
            );
        }

        $event = $this->eventClass::fromQueuePayload($this->payload);

        if (! $event instanceof MksineEvent) {
            throw new \RuntimeException(
                'Event rebuilt from queue must be an instance of MksineEvent. ' .
                'Got: ' . get_class($event)
            );
        }

        $listener = app()->make($this->listenerClass);

        if (! $listener instanceof MksineListenerInterface) {
            throw new \RuntimeException(
                'Listener must implement MksineListenerInterface. Got: ' . get_class($listener)
            );
        }

        if (! $listener->shouldHandle($event)) {
            return;
        }

        $listener->handle($event);
    }

    public function failed(?Throwable $exception): void
    {
        $logger = app()->bound('log') ? app('log') : null;

        if ($logger) {
            $logger->error('MKSine hook async listener failed', [
                'event_class' => $this->eventClass,
                'listener_class' => $this->listenerClass,
                'payload' => $this->payload,
                'exception' => $exception?->getMessage(),
                'trace' => $exception?->getTraceAsString(),
            ]);
        }
    }
}
