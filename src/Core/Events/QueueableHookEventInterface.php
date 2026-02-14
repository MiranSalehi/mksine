<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Events;

/**
 * Events that allow async execution MUST implement this interface.
 *
 * Contract: payload must contain only primitives and identifiers (e.g. model IDs).
 * No Eloquent models or other non-serializable values.
 * Payload MUST include a version key 'v' (integer) for version drift handling.
 *
 * Enforced at dispatch time: if allowAsync() === true and the event does not
 * implement this interface, the system throws.
 */
interface QueueableHookEventInterface
{
    /**
     * Serialize event state for queue payload.
     * MUST include key 'v' (integer). Only primitives and IDs.
     *
     * @return array{v: int, data: array<string, mixed>, context?: array<string, mixed>}
     */
    public function toQueuePayload(): array;

    /**
     * Rebuild event from queue payload. No direct constructor dependency.
     *
     * @param  array{v: int, data: array<string, mixed>, context?: array<string, mixed>}  $payload
     * @return static
     */
    public static function fromQueuePayload(array $payload): static;
}
