<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Listeners\Categories;

use Filament\Schemas\Schema;
use Miran\Mksine\Core\Hooks\FormHookListenerInterface;

/**
 * Example hook for `category.form` (core categories and Ecom product categories).
 */
class ExtendCategoryFormListener implements FormHookListenerInterface
{
    /**
     * Get the form name this listener extends.
     */
    public static function getFormName(): string
    {
        return 'category.form';
    }

    /**
     * Get the priority for hook execution.
     * Lower numbers execute first (e.g., 0 before 10).
     */
    public static function getPriority(): int
    {
        return 0;
    }

    /**
     * Extend the category form with additional fields.
     * This method is called by FormHookManager.
     *
     * Note: In Filament 4, calling components() will replace existing components.
     * To add components without replacing, you should modify the schema object directly
     * or use a different approach based on Filament 4's API.
     */
    public static function extend(Schema $schema): Schema
    {
        $existingComponents = method_exists($schema, 'getComponents')
            ? $schema->getComponents()
            : [];

        return $schema->components([...$existingComponents]);
    }
}
