<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

/**
 * Repository for hook state (enabled/disabled, priority overrides, system flags).
 *
 * This class reads state from the database but does NOT know about listener definitions.
 *
 * Responsibilities:
 * - Read enabled/disabled state for listeners
 * - Read priority overrides
 * - Read system flags
 * - Cache state for performance
 * - No listener definition awareness
 *
 * CORE IMMUTABILITY GUARANTEE:
 * ============================
 *
 * This class is marked as FINAL to prevent extension.
 * - No plugin or extension may replace or override HookStateRepository
 * - State reading contract is immutable and authoritative
 * - System hooks are always considered enabled (cannot be overridden)
 *
 * This ensures consistent state management across all hook executions.
 */
final class HookStateRepository
{
    /**
     * Cache of enabled/disabled state.
     * Structure: ['ClassName' => bool]
     *
     * @var array<string, bool>|null
     */
    private ?array $enabledStateCache = null;

    /**
     * Cache of priority overrides.
     * Structure: ['ClassName' => int]
     *
     * @var array<string, int>|null
     */
    private ?array $priorityOverrideCache = null;

    /**
     * Cache of system flags.
     * Structure: ['ClassName' => bool]
     *
     * @var array<string, bool>|null
     */
    private ?array $systemFlagCache = null;

    private function isDatabaseAvailable(): bool
    {
        if (! class_exists(\Illuminate\Support\Facades\DB::class)) {
            return false;
        }

        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('mks_hooks')) {
                return false;
            }

            \Illuminate\Support\Facades\DB::connection()->getPdo();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function loadState(): void
    {
        if (! $this->isDatabaseAvailable()) {
            $this->enabledStateCache = [];
            $this->priorityOverrideCache = [];
            $this->systemFlagCache = [];

            return;
        }

        try {
            $hooks = \Illuminate\Support\Facades\DB::table('mks_hooks')
                ->where('hook_type', 'event')
                ->select('listener_class', 'is_enabled', 'priority', 'is_system')
                ->get();

            $this->enabledStateCache = [];
            $this->priorityOverrideCache = [];
            $this->systemFlagCache = [];

            foreach ($hooks as $hook) {
                $listenerClass = $hook->listener_class;

                $this->enabledStateCache[$listenerClass] = (bool) ($hook->is_enabled ?? true);

                if (isset($hook->priority) && $hook->priority !== 0) {
                    $this->priorityOverrideCache[$listenerClass] = (int) $hook->priority;
                }

                $this->systemFlagCache[$listenerClass] = (bool) ($hook->is_system ?? false);
            }
        } catch (\Exception $e) {
            $this->enabledStateCache = [];
            $this->priorityOverrideCache = [];
            $this->systemFlagCache = [];
        }
    }

    /**
     * Check if a listener is enabled.
     *
     * System listeners (is_system = true) are always considered enabled,
     * even if is_enabled is false in the database.
     *
     * @param  string  $listenerClass  Fully qualified class name
     * @return bool True if enabled (default), false if explicitly disabled (and not system)
     */
    public function isEnabled(string $listenerClass): bool
    {
        if ($this->enabledStateCache === null) {
            $this->loadState();
        }

        if ($this->isSystem($listenerClass)) {
            return true;
        }

        return $this->enabledStateCache[$listenerClass] ?? true;
    }

    /**
     * Check if a listener is a system listener.
     *
     * @param  string  $listenerClass  Fully qualified class name
     */
    public function isSystem(string $listenerClass): bool
    {
        if ($this->systemFlagCache === null) {
            $this->loadState();
        }

        return $this->systemFlagCache[$listenerClass] ?? false;
    }

    public function getPriorityOverride(string $listenerClass): ?int
    {
        if ($this->priorityOverrideCache === null) {
            $this->loadState();
        }

        return $this->priorityOverrideCache[$listenerClass] ?? null;
    }

    public function getEffectivePriority(string $listenerClass, int $defaultPriority): int
    {
        $override = $this->getPriorityOverride($listenerClass);

        return $override ?? $defaultPriority;
    }

    public function clearCache(): void
    {
        $this->enabledStateCache = null;
        $this->priorityOverrideCache = null;
        $this->systemFlagCache = null;
    }
}
