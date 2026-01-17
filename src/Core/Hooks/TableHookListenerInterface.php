<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

use Filament\Tables\Table;

/**
 * Interface for table hook listeners.
 * Classes implementing this interface can be automatically discovered and registered.
 */
interface TableHookListenerInterface
{
    /**
     * Get the table name this listener extends.
     * Example: 'post.table', 'category.table'
     */
    public static function getTableName(): string;

    /**
     * Get the priority for hook execution.
     * Lower numbers execute first (e.g., 0 before 10).
     * Default is 0.
     */
    public static function getPriority(): int;

    /**
     * Extend the table.
     * This method is called by TableHookManager to modify the table.
     *
     * @param  Table  $table  The original table
     * @return Table The modified table
     */
    public static function extend(Table $table): Table;
}
