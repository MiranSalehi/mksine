<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

/**
 * Canonical representation of the Hook Execution Lifecycle.
 *
 * This class defines the immutable, authoritative contract for hook execution.
 * All hook execution MUST follow these phases in strict order.
 *
 * IMMUTABILITY GUARANTEE:
 * - This lifecycle is immutable and cannot be extended or modified
 * - No plugin or extension may bypass or reorder these phases
 * - The execution order is final and deterministic
 * - Core execution flow cannot be overridden
 *
 * @internal This is core platform behavior and cannot be extended
 */
final class HookLifecycle
{
    /**
     * Phase 1: Event Dispatch
     * The initial event is created and dispatched to HookManager.
     */
    public const PHASE_DISPATCH = 1;

    /**
     * Phase 2: Load Hook Definitions
     * HookRegistry provides listener definitions for the event name.
     * Each definition contains: listener class name and default priority.
     */
    public const PHASE_LOAD_DEFINITIONS = 2;

    /**
     * Phase 3: Merge Runtime State
     * HookStateRepository reads state from database:
     * - Enabled/disabled state
     * - Priority overrides
     * - System flags
     */
    public const PHASE_MERGE_STATE = 3;

    /**
     * Phase 4: Sort Listeners by Final Priority
     * Filter out disabled listeners (system hooks are never filtered).
     * Sort by effective priority (ascending: lower numbers execute first).
     * Stable sort ensures deterministic order for equal priorities.
     */
    public const PHASE_SORT = 4;

    /**
     * Phase 5: Execute Synchronous Listeners
     * For each listener in sorted order:
     * - Check if should queue (skip, collect for async)
     * - Check if should handle (skip if false)
     * - Execute listener
     * - Check prevention (stop immediately if prevented)
     */
    public const PHASE_EXECUTE_SYNC = 5;

    /**
     * Phase 6: Stop Execution Immediately on Prevent
     * If event->prevent() was called:
     * - All remaining listeners are skipped
     * - Execution breaks immediately
     * - No further mutations are allowed
     */
    public const PHASE_STOP_ON_PREVENT = 6;

    /**
     * Phase 7: Dispatch Async Listeners (If Event Allows)
     * If event->isAsyncAllowed() returns true:
     * - Process pendingAsyncListeners array
     * - Queue for async execution (handled by external queue system)
     */
    public const PHASE_DISPATCH_ASYNC = 7;

    /**
     * Phase 8: Return Execution Result
     * EventResult contains:
     * - wasPrevented: Boolean
     * - preventReason: String or null
     * - mutations: Array of all mutations applied
     * - pendingAsyncListeners: Array of listener class names
     */
    public const PHASE_RETURN_RESULT = 8;

    /**
     * Total number of lifecycle phases.
     */
    public const TOTAL_PHASES = 8;

    /**
     * Get all lifecycle phases in order.
     *
     * @return array<int, string> Map of phase number to phase name
     */
    public static function getPhases(): array
    {
        return [
            self::PHASE_DISPATCH => 'Event Dispatch',
            self::PHASE_LOAD_DEFINITIONS => 'Load Hook Definitions',
            self::PHASE_MERGE_STATE => 'Merge Runtime State',
            self::PHASE_SORT => 'Sort Listeners by Final Priority',
            self::PHASE_EXECUTE_SYNC => 'Execute Synchronous Listeners',
            self::PHASE_STOP_ON_PREVENT => 'Stop Execution Immediately on Prevent',
            self::PHASE_DISPATCH_ASYNC => 'Dispatch Async Listeners (If Event Allows)',
            self::PHASE_RETURN_RESULT => 'Return Execution Result',
        ];
    }

    /**
     * Verify that the lifecycle phases are correctly ordered.
     */
    public static function verifyOrder(): bool
    {
        $phases = self::getPhases();
        $expected = range(1, self::TOTAL_PHASES);
        $actual = array_keys($phases);

        return $expected === $actual;
    }

    /**
     * This class cannot be instantiated.
     */
    private function __construct()
    {
        throw new \LogicException('HookLifecycle is a static contract class and cannot be instantiated');
    }
}
