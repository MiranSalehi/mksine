<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Events;

/**
 * Read-only wrapper for event data.
 * Prevents accidental mutation of event data.
 */
final class EventDataBag
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private readonly array $data
    ) {}

    /**
     * Get a value from the data bag.
     * Supports dot notation for nested data.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Support dot notation for nested data
        if (str_contains($key, '.')) {
            return data_get($this->data, $key, $default);
        }

        return $this->data[$key] ?? $default;
    }

    /**
     * Check if a key exists in the data bag.
     * Supports dot notation for nested data.
     */
    public function has(string $key): bool
    {
        // Support dot notation for nested data
        if (str_contains($key, '.')) {
            return data_get($this->data, $key) !== null || \Illuminate\Support\Arr::has($this->data, $key);
        }

        return array_key_exists($key, $this->data);
    }

    /**
     * Get all data as an array.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }
}
