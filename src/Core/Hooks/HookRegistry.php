<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

/**
 * Registry of hook definitions discovered from code.
 *
 * This class holds listener definitions (event name, listener class, priority)
 * but does NOT know about runtime state (enabled/disabled, priority overrides).
 *
 * Responsibilities:
 * - Store listener definitions
 * - Provide lookup by event name
 * - Provide lookup by listener class
 * - No database awareness
 *
 * CORE IMMUTABILITY GUARANTEE:
 * ============================
 *
 * This class is marked as FINAL to prevent extension.
 * - No plugin or extension may replace or override HookRegistry
 * - The registry contract is immutable and authoritative
 * - Listener definitions cannot be modified during execution
 *
 * This ensures deterministic behavior and prevents unauthorized modifications
 * to the core hook system.
 */
final class HookRegistry
{
    /**
     * Registry of listeners by event name.
     * Structure: ['event.name' => [['listener' => 'ClassName', 'priority' => 0], ...]]
     *
     * @var array<string, array<array{listener: string, priority: int}>>
     */
    private array $registry = [];

    /**
     * Register a listener for a specific event.
     *
     * @param  string  $eventName  The event name (e.g., 'post.publishing')
     * @param  string  $listenerClass  Fully qualified class name of the listener
     * @param  int  $priority  Lower numbers execute first (default: 0)
     */
    public function register(string $eventName, string $listenerClass, int $priority = 0): void
    {
        if (! isset($this->registry[$eventName])) {
            $this->registry[$eventName] = [];
        }

        $this->registry[$eventName][] = [
            'listener' => $listenerClass,
            'priority' => $priority,
        ];
    }

    /**
     * Get all registered listeners for an event name.
     *
     * @return array<array{listener: string, priority: int}>
     */
    public function getListenersForEvent(string $eventName): array
    {
        return $this->registry[$eventName] ?? [];
    }

    /**
     * Get all registered events and their listeners.
     *
     * @return array<string, array<array{listener: string, priority: int}>>
     */
    public function all(): array
    {
        return $this->registry;
    }

    /**
     * Check if a listener is registered for any event.
     */
    public function hasListener(string $listenerClass): bool
    {
        foreach ($this->registry as $listeners) {
            foreach ($listeners as $listenerConfig) {
                if ($listenerConfig['listener'] === $listenerClass) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Clear all registered listeners.
     * Useful for testing.
     */
    public function clear(): void
    {
        $this->registry = [];
    }
}
