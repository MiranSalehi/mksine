<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Events;

/**
 * Abstract base class for all CMS events.
 * Provides controlled data mutation, event prevention, and mutation tracking.
 */
abstract class MksineEvent
{
    /**
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * @var array<string, mixed>
     */
    private readonly array $context;

    /**
     * @var array<array{key: string, old: mixed, new: mixed}>
     */
    private array $mutations = [];

    private bool $prevented = false;

    private ?string $preventReason = null;

    private bool $asyncAllowed = false;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $context
     */
    public function __construct(array $data, array $context = [])
    {
        $this->data = $data;
        $this->context = $context;
        $this->asyncAllowed = $this->allowAsync();
    }

    /**
     * Get read-only access to event data.
     */
    public function data(): EventDataBag
    {
        return new EventDataBag($this->data);
    }

    /**
     * Get event context.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    /**
     * Update a specific data key.
     * Throws if the event is already prevented.
     *
     * @throws \RuntimeException If event is prevented
     */
    public function updateData(string $key, mixed $value): void
    {
        if ($this->prevented) {
            throw new \RuntimeException(
                'Cannot update data on a prevented event: ' . $this->name()
            );
        }

        $oldValue = $this->data[$key] ?? null;
        $this->data[$key] = $value;

        $this->mutations[] = [
            'key' => $key,
            'old' => $oldValue,
            'new' => $value,
        ];
    }

    /**
     * Get all data as an array.
     *
     * @return array<string, mixed>
     */
    public function allData(): array
    {
        return $this->data;
    }

    /**
     * Get all mutations that have been applied.
     *
     * @return array<array{key: string, old: mixed, new: mixed}>
     */
    public function mutations(): array
    {
        return $this->mutations;
    }

    /**
     * Revert mutations back to a specific count.
     * Used when a listener fails after making mutations to restore the state.
     *
     * @param  int  $targetCount  Target number of mutations to keep (0-based index)
     */
    public function revertMutationsToCount(int $targetCount): void
    {
        if ($targetCount < 0 || $targetCount >= count($this->mutations)) {
            return;
        }

        // Remove mutations after the target count
        $this->mutations = array_slice($this->mutations, 0, $targetCount);
    }

    /**
     * Prevent the event from continuing.
     * Throws if the event cannot be prevented.
     *
     * @throws \RuntimeException If event cannot be prevented
     */
    public function prevent(string $reason): void
    {
        if (! $this->canBePrevented()) {
            throw new \RuntimeException(
                'Event cannot be prevented: ' . $this->name()
            );
        }

        $this->prevented = true;
        $this->preventReason = $reason;
    }

    /**
     * Check if the event has been prevented.
     */
    public function isPrevented(): bool
    {
        return $this->prevented;
    }

    /**
     * Get the reason why the event was prevented.
     */
    public function preventReason(): ?string
    {
        return $this->preventReason;
    }

    /**
     * Check if async execution is allowed for this event.
     */
    public function isAsyncAllowed(): bool
    {
        return $this->asyncAllowed;
    }

    /**
     * Get the event name.
     */
    abstract public function name(): string;

    /**
     * Check if this event can be prevented.
     */
    abstract public function canBePrevented(): bool;

    /**
     * Override this method to allow async execution.
     * Default is false.
     */
    protected function allowAsync(): bool
    {
        return false;
    }
}
