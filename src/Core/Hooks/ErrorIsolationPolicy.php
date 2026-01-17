<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

/**
 * Error Isolation Policy for Hook System.
 *
 * TASK 3: Formalized error handling behavior WITHOUT changing execution flow.
 *
 * This class documents the canonical error isolation policy enforced by HookDispatcher.
 *
 * POLICY RULES:
 * ============
 *
 * 1. Fatal/Throwable Errors Inside a Listener:
 *    - MUST be logged with plugin + hook context
 *    - MUST revert event mutations made by the failed listener
 *    - MUST NOT break system hooks (system hooks re-throw to ensure critical functionality)
 *    - Non-system hooks continue execution with remaining listeners
 *
 * 2. Non-Fatal Warnings/Notices:
 *    - MUST NOT disable plugins
 *    - MUST NOT break execution flow
 *    - MAY be logged for debugging purposes
 *
 * 3. Mutation Reversion:
 *    - All mutations made by a failed listener are automatically reverted
 *    - Data state is restored to pre-listener-execution state
 *    - Mutations array is cleaned to remove failed listener's mutations
 *
 * 4. System Hook Protection:
 *    - System hooks that fail MUST re-throw the exception
 *    - This ensures critical functionality is not silently skipped
 *    - Non-system hooks are isolated and do not affect other listeners
 *
 * 5. Error Context:
 *    - All errors MUST include:
 *      - Listener class name
 *      - Plugin identifier (plugin_id)
 *      - Hook name (hook_name)
 *      - Hook owner (hook_owner)
 *      - Event name
 *      - Error message and trace
 *      - Number of mutations reverted
 *
 * ENFORCEMENT:
 * ===========
 *
 * This policy is enforced in HookDispatcher::dispatch() method.
 * The policy is immutable and cannot be overridden.
 *
 * FUTURE EXTENSIONS:
 * =================
 *
 * - Auto-disable logic (not implemented yet)
 * - Queue/async error handling (not implemented yet)
 * - Error aggregation and reporting (not implemented yet)
 *
 * @internal This is core platform behavior and cannot be extended
 */
final class ErrorIsolationPolicy
{
    /**
     * This class cannot be instantiated.
     */
    private function __construct()
    {
        throw new \LogicException('ErrorIsolationPolicy is a static contract class and cannot be instantiated');
    }
}
