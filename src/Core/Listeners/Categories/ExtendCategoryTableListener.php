<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Listeners\Categories;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Miran\Mksine\Core\Hooks\TableHookListenerInterface;

/**
 * Example class that extends the Category table with additional columns.
 * This listener will be automatically discovered by mks:discover command.
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
        // Get existing columns
        $existingColumns = method_exists($table, 'getColumns')
            ? $table->getColumns()
            : [];

        // Add new column to existing columns
        return $table->columns([
            ...$existingColumns,
            TextColumn::make('custom_field')
                ->label('Custom Field')
                ->searchable()
                ->sortable()
                ->toggleable(),
        ]);
    }
}
