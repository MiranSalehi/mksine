<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

use Filament\Schemas\Schema;

/**
 * Interface for form hook listeners.
 * Classes implementing this interface can be automatically discovered and registered.
 */
interface FormHookListenerInterface
{
    /**
     * Get the form name this listener extends.
     * Example: 'post.form', 'category.form'
     */
    public static function getFormName(): string;

    /**
     * Get the priority for hook execution.
     * Lower numbers execute first (e.g., 0 before 10).
     * Default is 0.
     */
    public static function getPriority(): int;

    /**
     * Extend the form schema.
     * This method is called by FormHookManager to modify the form.
     *
     * @param  Schema  $schema  The original schema
     * @return Schema The modified schema
     */
    public static function extend(Schema $schema): Schema;
}
