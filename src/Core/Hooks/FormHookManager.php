<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;

/**
 * Manager for extending forms through hooks.
 * Supports whole-form callbacks and named before/after/replace slot hooks.
 */
class FormHookManager
{
    /**
     * @var array<string, array<callable>>
     */
    private array $hooks = [];

    /**
     * @var array<string, array<string, array<string, array<int, array{callback: callable, priority: int}>>>>
     */
    private array $slots = [];

    /**
     * Register a hook to extend a form.
     *
     * @param  string  $formName  The form identifier (e.g., 'post.form')
     * @param  callable  $callback  Callback that receives Schema and returns modified Schema
     */
    public function extend(string $formName, callable $callback): void
    {
        if (! isset($this->hooks[$formName])) {
            $this->hooks[$formName] = [];
        }

        $this->hooks[$formName][] = $callback;
    }

    /**
     * Register a named slot hook for a form component or section.
     *
     * @param  string  $formName  The form identifier (e.g., 'post.form')
     * @param  string  $position  before|after|replace
     * @param  string  $anchor  Field name or "{sectionKey}_section"
     * @param  callable  $callback  before/after: fn ($original): Component|array; replace: fn ($original): Component|array|null
     */
    public function extendSlot(
        string $formName,
        string $position,
        string $anchor,
        callable $callback,
        int $priority = 0,
    ): void {
        $position = strtolower($position);

        if (! in_array($position, ['before', 'after', 'replace'], true)) {
            Log::error("FormHookManager: Invalid slot position '{$position}' for '{$formName}'.");

            return;
        }

        if ($anchor === '') {
            Log::error("FormHookManager: Empty slot anchor for '{$formName}'.");

            return;
        }

        $this->slots[$formName][$position][$anchor][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];
    }

    /**
     * Apply all registered hooks to a form schema.
     *
     * Whole-form callbacks run first; named slot hooks run afterward.
     *
     * @param  string  $formName  The form identifier
     * @param  Schema  $schema  The original schema
     * @return Schema The modified schema
     */
    public function apply(string $formName, Schema $schema): Schema
    {
        foreach ($this->hooks[$formName] ?? [] as $callback) {
            try {
                $result = $callback($schema);

                if ($result instanceof Schema) {
                    $schema = $result;
                }
            } catch (\Exception $e) {
                Log::error("FormHookManager: Error executing callback for '{$formName}': ".$e->getMessage());
            }
        }

        $slots = $this->slots[$formName] ?? [];

        if ($slots !== []) {
            $schema = app(FormSlotApplicator::class)->apply($formName, $schema, $slots);
        }

        return $schema;
    }

    /**
     * Clear all hooks for a form (whole-form and slots).
     */
    public function clear(string $formName): void
    {
        unset($this->hooks[$formName], $this->slots[$formName]);
    }

    /**
     * Clear every registered form and slot hook (useful in tests).
     */
    public function clearAll(): void
    {
        $this->hooks = [];
        $this->slots = [];
    }
}
