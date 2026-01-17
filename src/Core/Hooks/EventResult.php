<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

/**
 * Value object representing the result of event dispatching.
 */
final class EventResult
{
    /**
     * @param  bool  $wasPrevented  Whether the event was prevented
     * @param  string|null  $preventReason  Reason for prevention, if any
     * @param  array<array{key: string, old: mixed, new: mixed}>  $mutations  All mutations applied to the event
     * @param  array<string>  $pendingAsyncListeners  Class names of listeners that should be queued
     */
    public function __construct(
        private readonly bool $wasPrevented,
        private readonly ?string $preventReason,
        private readonly array $mutations,
        private readonly array $pendingAsyncListeners
    ) {}

    /**
     * Check if the event was prevented.
     */
    public function wasPrevented(): bool
    {
        return $this->wasPrevented;
    }

    /**
     * Get the reason why the event was prevented.
     */
    public function preventReason(): ?string
    {
        return $this->preventReason;
    }

    /**
     * Get all mutations that were applied to the event.
     *
     * @return array<array{key: string, old: mixed, new: mixed}>
     */
    public function mutations(): array
    {
        return $this->mutations;
    }

    /**
     * Get class names of listeners that should be queued for async execution.
     *
     * @return array<string>
     */
    public function pendingAsyncListeners(): array
    {
        return $this->pendingAsyncListeners;
    }
}
