<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

use Illuminate\Support\Facades\Log;
use Miran\Mksine\Core\Events\HookListenerExecuted;
use Miran\Mksine\Core\Events\MksineEvent;
use Throwable;

/**
 * Dispatcher responsible for executing hooks in priority order.
 *
 * This class handles:
 * - Executing listeners in strict priority order
 * - Handling sync vs async listeners
 * - Stopping execution on prevent
 * - Collecting mutations and async listeners
 * - Enforcing system hooks (MUST execute even if disabled)
 *
 * Responsibilities:
 * - Execute listeners
 * - Handle execution flow (sync/async, prevention)
 * - Enforce system hook execution (cannot be bypassed)
 * - No registry or state awareness (receives prepared list with system flags)
 *
 * CORE IMMUTABILITY:
 * - System hooks (is_system = true) MUST execute even if disabled
 * - This enforcement happens at dispatch-time and cannot be bypassed
 * - No plugin or extension may override this behavior
 */
final class HookDispatcher
{
    /**
     * Cache of instantiated listeners.
     * Structure: ['ClassName' => MksineListenerInterface]
     *
     * @var array<string, MksineListenerInterface>
     */
    private array $listenerInstances = [];

    /**
     * Dispatch an event to the provided listeners.
     *
     * Listeners should be pre-sorted by priority.
     * System hooks (is_system = true) are ALWAYS executed regardless of enabled state.
     *
     * ENFORCEMENT RULE:
     * - is_system listeners MUST execute even if is_enabled = false in database
     * - This is enforced at dispatch-time and cannot be bypassed
     * - Database state, UI overrides, or ServiceProvider cannot disable system hooks
     *
     * @param  MksineEvent  $event  The event to dispatch
     * @param  array<array{listener: string, priority: int, is_system: bool, is_enabled: bool}>  $listeners  Pre-sorted listeners with system and enabled flags
     * @return EventResult Result containing prevention status, mutations, and async listeners
     */
    public function dispatch(MksineEvent $event, array $listeners): EventResult
    {
        $pendingAsyncListeners = [];

        foreach ($listeners as $listenerConfig) {
            $listenerClass = $listenerConfig['listener'];
            $isSystem = $listenerConfig['is_system'] ?? false;
            $isEnabled = $listenerConfig['is_enabled'] ?? true;

            if (! $isEnabled && ! $isSystem) {
                continue;
            }

            $listener = $this->getListenerInstance($listenerClass);

            if ($listener->shouldQueue()) {
                $pendingAsyncListeners[] = $listenerClass;
                $this->reportExecution($event, $listenerClass, 0.0, queued: true, isSystem: $isSystem);

                continue;
            }

            if (! $listener->shouldHandle($event)) {
                continue;
            }

            $started = hrtime(true);
            $listener->handle($event);
            $elapsedMs = (hrtime(true) - $started) / 1_000_000;

            $this->reportExecution($event, $listenerClass, $elapsedMs, queued: false, isSystem: $isSystem);

            if ($event->isPrevented()) {
                break;
            }
        }

        $allMutations = $event->mutations();

        return new EventResult(
            wasPrevented: $event->isPrevented(),
            preventReason: $event->preventReason(),
            mutations: $allMutations,
            pendingAsyncListeners: $pendingAsyncListeners
        );
    }

    private function getListenerInstance(string $listenerClass): MksineListenerInterface
    {
        if (isset($this->listenerInstances[$listenerClass])) {
            return $this->listenerInstances[$listenerClass];
        }

        if (! class_exists($listenerClass)) {
            throw new \RuntimeException("Listener class does not exist: {$listenerClass}");
        }

        if (class_exists(\Illuminate\Container\Container::class)) {
            $container = \Illuminate\Container\Container::getInstance();
            $listener = $container->make($listenerClass);
        } else {
            $listener = new $listenerClass;
        }

        if (! $listener instanceof MksineListenerInterface) {
            throw new \RuntimeException(
                "Listener class must implement MksineListenerInterface: {$listenerClass}"
            );
        }

        $this->listenerInstances[$listenerClass] = $listener;

        return $listener;
    }

    public function clearCache(): void
    {
        $this->listenerInstances = [];
    }

    private function reportExecution(
        MksineEvent $event,
        string $listenerClass,
        float $elapsedMs,
        bool $queued,
        bool $isSystem,
    ): void {
        $threshold = $this->slowThresholdMs();
        $rounded = round($elapsedMs, 2);

        if (! $queued && $this->shouldLogSlowHooks() && $elapsedMs > $threshold) {
            try {
                Log::warning('mksine.slow_hook', [
                    'listener' => $listenerClass,
                    'event' => $event->name(),
                    'ms' => $rounded,
                    'threshold_ms' => $threshold,
                    'prevented' => $event->isPrevented(),
                ]);
            } catch (Throwable) {
                //
            }
        }

        if (! function_exists('event')) {
            return;
        }

        try {
            event(new HookListenerExecuted(
                eventName: $event->name(),
                listenerClass: $listenerClass,
                elapsedMs: $rounded,
                queued: $queued,
                prevented: $event->isPrevented(),
                isSystem: $isSystem,
            ));
        } catch (Throwable) {
            //
        }
    }

    private function shouldLogSlowHooks(): bool
    {
        if (! function_exists('config')) {
            return true;
        }

        try {
            return (bool) config('mksine.hooks.log_slow_hooks', true);
        } catch (Throwable) {
            return true;
        }
    }

    private function slowThresholdMs(): int
    {
        if (! function_exists('config')) {
            return 100;
        }

        try {
            return (int) config('mksine.hooks.slow_hook_threshold', 100);
        } catch (Throwable) {
            return 100;
        }
    }
}
