<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

use Miran\Mksine\Core\Events\MksineEvent;

/**
 * Central coordinator for the CMS hook system.
 *
 * This class coordinates HookRegistry, HookStateRepository, and HookDispatcher
 * to provide a unified public API for hook management.
 *
 * Responsibilities:
 * - Coordinate registry + state + dispatcher
 * - Provide public API for registration and dispatch
 * - Maintain backward compatibility with existing code
 *
 * CORE IMMUTABILITY GUARANTEES:
 * ==============================
 *
 * This is a platform kernel component. The following invariants are ENFORCED:
 *
 * 1. Hook Lifecycle Execution Order:
 *    - All hook execution MUST follow the 8-phase lifecycle defined in HookLifecycle
 *    - The execution order is FINAL and DETERMINISTIC
 *    - No plugin, extension, or subclass may bypass or reorder lifecycle phases
 *    - See HookLifecycle class for the canonical lifecycle definition
 *
 * 2. HookDispatcher Immutability:
 *    - HookDispatcher is marked FINAL and cannot be extended
 *    - System hooks (is_system = true) MUST execute even if disabled in database
 *    - This enforcement happens at dispatch-time inside HookDispatcher and cannot be bypassed
 *    - Database state, UI overrides, or ServiceProvider cannot disable system hooks
 *
 * 3. HookRegistry Immutability:
 *    - HookRegistry is marked FINAL and cannot be replaced or overridden
 *    - Listener definitions are authoritative and cannot be modified during execution
 *
 * 4. HookStateRepository Immutability:
 *    - HookStateRepository is marked FINAL and cannot be replaced
 *    - System hook state is always enabled regardless of database value
 *
 * 5. Execution Flow Determinism:
 *    - Execution flow is final and deterministic
 *    - No external code may inject custom execution logic
 *    - All execution follows the canonical lifecycle contract
 *
 * These guarantees ensure the hook system behaves predictably and securely
 * across all installations and use cases.
 */
class HookManager
{
    private HookRegistry $registry;

    private HookStateRepository $stateRepository;

    private HookDispatcher $dispatcher;

    public function __construct(
        ?HookRegistry $registry = null,
        ?HookStateRepository $stateRepository = null,
        ?HookDispatcher $dispatcher = null
    ) {
        $this->registry = $registry ?? new HookRegistry;
        $this->stateRepository = $stateRepository ?? new HookStateRepository;
        $this->dispatcher = $dispatcher ?? new HookDispatcher;
    }

    /**
     * Register a listener for a specific event.
     *
     * @param  string  $eventName  The event name (e.g., 'post.publishing')
     * @param  string  $listenerClass  Fully qualified class name of the listener
     * @param  int  $priority  Lower numbers execute first (default: 0)
     */
    public function register(string $eventName, string $listenerClass, int $priority = 0): void
    {
        $this->registry->register($eventName, $listenerClass, $priority);
    }

    /**
     * Enable a listener.
     *
     * @param  string  $listenerClass  Fully qualified class name
     *
     * @deprecated State is managed via database. Update mks_hooks table instead. This method will be removed in a future version.
     */
    public function enableListener(string $listenerClass): void
    {
        if ($this->shouldEmitDeprecationWarning()) {
            @trigger_error(
                sprintf(
                    'HookManager::enableListener() is deprecated and has no effect. ' .
                    'Hook state is now managed automatically from the database (mks_hooks table). ' .
                    'Update the database directly instead. Called for listener: %s',
                    $listenerClass
                ),
                E_USER_DEPRECATED
            );
        }
    }

    /**
     * Disable a listener.
     *
     * @param  string  $listenerClass  Fully qualified class name
     *
     * @deprecated State is managed via database. Update mks_hooks table instead. This method will be removed in a future version.
     */
    public function disableListener(string $listenerClass): void
    {
        if ($this->shouldEmitDeprecationWarning()) {
            @trigger_error(
                sprintf(
                    'HookManager::disableListener() is deprecated and has no effect. ' .
                    'Hook state is now managed automatically from the database (mks_hooks table). ' .
                    'Update the database directly instead. Called for listener: %s',
                    $listenerClass
                ),
                E_USER_DEPRECATED
            );
        }
    }

    /**
     * Override the priority of a listener.
     *
     * @param  string  $listenerClass  Fully qualified class name
     * @param  int  $priority  New priority
     *
     * @deprecated Priority overrides are managed via database. Update mks_hooks table instead. This method will be removed in a future version.
     */
    public function setPriority(string $listenerClass, int $priority): void
    {
        if ($this->shouldEmitDeprecationWarning()) {
            @trigger_error(
                sprintf(
                    'HookManager::setPriority() is deprecated and has no effect. ' .
                    'Priority overrides are now managed automatically from the database (mks_hooks table). ' .
                    'Update the database directly instead. Called for listener: %s with priority: %d',
                    $listenerClass,
                    $priority
                ),
                E_USER_DEPRECATED
            );
        }
    }

    private function shouldEmitDeprecationWarning(): bool
    {
        if (function_exists('app') && class_exists(\Illuminate\Foundation\Application::class)) {
            try {
                return config('app.debug', false);
            } catch (\Exception $e) {
                return false;
            }
        }

        return (error_reporting() & E_USER_DEPRECATED) !== 0;
    }

    /**
     * Check if a listener is enabled.
     *
     * @param  string  $listenerClass  Fully qualified class name
     * @return bool True if enabled (default), false if explicitly disabled (unless system)
     */
    public function isListenerEnabled(string $listenerClass): bool
    {
        return $this->stateRepository->isEnabled($listenerClass);
    }

    /**
     * Dispatch an event to all registered listeners.
     * Executes listeners in strict priority order (lower numbers first).
     * Stops execution immediately if the event is prevented.
     *
     * EXECUTION LIFECYCLE:
     * This method follows the canonical 8-phase lifecycle defined in HookLifecycle:
     * 1. Event Dispatch (caller responsibility)
     * 2. Load Hook Definitions (HookRegistry::getListenersForEvent)
     * 3. Merge Runtime State (HookStateRepository::isEnabled, isSystem, getEffectivePriority)
     * 4. Sort Listeners by Final Priority (this method)
     * 5. Execute Synchronous Listeners (HookDispatcher::dispatch)
     * 6. Stop Execution Immediately on Prevent (HookDispatcher::dispatch)
     * 7. Dispatch Async Listeners (HookDispatcher::dispatch, if event allows)
     * 8. Return Execution Result (this method)
     *
     * This lifecycle is IMMUTABLE and cannot be bypassed or modified.
     * No plugin or extension may override this execution flow.
     *
     * @param  MksineEvent  $event  The event to dispatch
     * @return EventResult Result containing prevention status, mutations, and async listeners
     */
    public function dispatch(MksineEvent $event): EventResult
    {
        $eventName = $event->name();
        $listeners = $this->registry->getListenersForEvent($eventName);

        $filteredListeners = [];
        foreach ($listeners as $listenerConfig) {
            $listenerClass = $listenerConfig['listener'];
            $isSystem = $this->stateRepository->isSystem($listenerClass);
            $isEnabled = $this->stateRepository->isEnabled($listenerClass);

            if (! $isEnabled && ! $isSystem) {
                continue;
            }

            $effectivePriority = $this->stateRepository->getEffectivePriority(
                $listenerClass,
                $listenerConfig['priority']
            );

            $filteredListeners[] = [
                'listener' => $listenerClass,
                'priority' => $effectivePriority,
                'is_system' => $isSystem,
                'is_enabled' => $isEnabled,
            ];
        }

        usort($filteredListeners, function (array $a, array $b): int {
            return $a['priority'] <=> $b['priority'];
        });

        return $this->dispatcher->dispatch($event, $filteredListeners);
    }

    /**
     * Get all registered events and their listeners.
     *
     * @return array<string, array<array{listener: string, priority: int}>>
     */
    public function getRegisteredListeners(): array
    {
        return $this->registry->all();
    }

    /**
     * Clear the listener instance cache.
     * Useful for testing or when listeners need to be re-instantiated.
     */
    public function clearCache(): void
    {
        $this->dispatcher->clearCache();
        $this->stateRepository->clearCache();
    }
}
