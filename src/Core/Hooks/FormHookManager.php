<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

use Filament\Schemas\Schema;

/**
 * Manager for extending forms through hooks.
 * Allows listeners to add or modify form components.
 */
class FormHookManager
{
    /**
     * @var array<string, array<callable>>
     */
    private array $hooks = [];

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
     * Apply all registered hooks to a form schema.
     *
     * @param  string  $formName  The form identifier
     * @param  Schema  $schema  The original schema
     * @return Schema The modified schema
     */
    public function apply(string $formName, Schema $schema): Schema
    {
        if (! isset($this->hooks[$formName])) {
            return $schema;
        }

        foreach ($this->hooks[$formName] as $callback) {
            try {
                // Call the callback with the schema
                // The callback should return a modified schema
                $result = $callback($schema);

                // If callback returns a schema, use it; otherwise keep the original
                if ($result instanceof Schema) {
                    $schema = $result;
                }
            } catch (\Exception $e) {
                // Log error but continue with other hooks
                \Illuminate\Support\Facades\Log::error("FormHookManager: Error executing callback for '{$formName}': " . $e->getMessage());
            }
        }

        return $schema;
    }

    /**
     * Clear all hooks for a form.
     */
    public function clear(string $formName): void
    {
        unset($this->hooks[$formName]);
    }
}
