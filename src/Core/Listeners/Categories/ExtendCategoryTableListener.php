<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Listeners\Categories;

use Filament\Tables\Table;
use Miran\Mksine\Core\Hooks\TableHookListenerInterface;

/**
 * Example hook for `category.table` (core categories and Ecom product categories).
 *
 * Only mark columns searchable or sortable when they exist on the model table; otherwise
 * Filament global search will fail with “unknown column” SQL errors.
 */
class ExtendCategoryTableListener implements TableHookListenerInterface
{
    /**
     * Get the table name this listener extends.
     */
    public static function getTableName(): string
    {
        return 'category.table';
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
     * Extend the category table with additional columns.
     * This method is called by TableHookManager.
     *
     * Note: In Filament 4, calling columns() will replace existing columns.
     * To add columns without replacing, you should modify the table object directly
     * or use a different approach based on Filament 4's API.
     */
    public static function extend(Table $table): Table
    {
        $existingColumns = method_exists($table, 'getColumns')
            ? $table->getColumns()
            : [];

        return $table->columns([...$existingColumns]);
    }
}
