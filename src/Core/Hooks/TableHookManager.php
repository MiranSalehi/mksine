<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

use Filament\Tables\Table;

/**
 * Manager for extending tables through hooks.
 * Allows listeners to add or modify table columns, filters, actions, and bulk actions.
 */
class TableHookManager
{
    /**
     * @var array<string, array<callable>>
     */
    private array $hooks = [];

    /**
     * @var array<string, array<callable>>
     */
    private array $columnHooks = [];

    /**
     * @var array<string, array<callable>>
     */
    private array $actionHooks = [];

    /**
     * @var array<string, array<callable>>
     */
    private array $bulkActionHooks = [];

    /**
     * @var array<string, array<callable>>
     */
    private array $filterHooks = [];

    /**
     * Register a hook to extend a table (full table modification).
     *
     * @param  string  $tableName  The table identifier (e.g., 'post.table')
     * @param  callable  $callback  Callback that receives Table and returns modified Table
     */
    public function extend(string $tableName, callable $callback): void
    {
        if (! isset($this->hooks[$tableName])) {
            $this->hooks[$tableName] = [];
        }

        $this->hooks[$tableName][] = $callback;
    }

    /**
     * Register a hook to modify columns (e.g., make searchable, sortable).
     * Callback receives Table object and should return modified Table.
     *
     * @param  string  $tableName  The table identifier
     * @param  callable  $callback  Callback that receives Table and returns modified Table
     */
    public function extendColumns(string $tableName, callable $callback): void
    {
        if (! isset($this->columnHooks[$tableName])) {
            $this->columnHooks[$tableName] = [];
        }

        $this->columnHooks[$tableName][] = $callback;
    }

    /**
     * Register a hook to add record actions.
     * Callback receives Table object and should return modified Table.
     *
     * @param  string  $tableName  The table identifier
     * @param  callable  $callback  Callback that receives Table and returns modified Table
     */
    public function extendActions(string $tableName, callable $callback): void
    {
        if (! isset($this->actionHooks[$tableName])) {
            $this->actionHooks[$tableName] = [];
        }

        $this->actionHooks[$tableName][] = $callback;
    }

    /**
     * Register a hook to add bulk actions.
     * Callback receives Table object and should return modified Table.
     *
     * @param  string  $tableName  The table identifier
     * @param  callable  $callback  Callback that receives Table and returns modified Table
     */
    public function extendBulkActions(string $tableName, callable $callback): void
    {
        if (! isset($this->bulkActionHooks[$tableName])) {
            $this->bulkActionHooks[$tableName] = [];
        }

        $this->bulkActionHooks[$tableName][] = $callback;
    }

    /**
     * Register a hook to add or modify filters.
     * Callback receives Table object and should return modified Table.
     *
     * @param  string  $tableName  The table identifier
     * @param  callable  $callback  Callback that receives Table and returns modified Table
     */
    public function extendFilters(string $tableName, callable $callback): void
    {
        if (! isset($this->filterHooks[$tableName])) {
            $this->filterHooks[$tableName] = [];
        }

        $this->filterHooks[$tableName][] = $callback;
    }

    /**
     * Apply all registered hooks to a table.
     *
     * @param  string  $tableName  The table identifier
     * @param  Table  $table  The original table
     * @return Table The modified table
     */
    public function apply(string $tableName, Table $table): Table
    {
        // Apply full table hooks first (most flexible)
        if (isset($this->hooks[$tableName])) {
            foreach ($this->hooks[$tableName] as $callback) {
                $result = $callback($table);
                if ($result instanceof Table) {
                    $table = $result;
                }
            }
        }

        // Apply column hooks
        // Note: In Filament 4, we need to work with the table object directly
        // The callback receives the table and can modify columns using table methods
        if (isset($this->columnHooks[$tableName])) {
            foreach ($this->columnHooks[$tableName] as $callback) {
                $result = $callback($table);
                if ($result instanceof Table) {
                    $table = $result;
                }
            }
        }

        // Apply action hooks
        if (isset($this->actionHooks[$tableName])) {
            foreach ($this->actionHooks[$tableName] as $callback) {
                $result = $callback($table);
                if ($result instanceof Table) {
                    $table = $result;
                }
            }
        }

        // Apply bulk action hooks
        if (isset($this->bulkActionHooks[$tableName])) {
            foreach ($this->bulkActionHooks[$tableName] as $callback) {
                $result = $callback($table);
                if ($result instanceof Table) {
                    $table = $result;
                }
            }
        }

        // Apply filter hooks
        if (isset($this->filterHooks[$tableName])) {
            foreach ($this->filterHooks[$tableName] as $callback) {
                $result = $callback($table);
                if ($result instanceof Table) {
                    $table = $result;
                }
            }
        }

        return $table;
    }

    /**
     * Clear all hooks for a table.
     */
    public function clear(string $tableName): void
    {
        unset($this->hooks[$tableName]);
        unset($this->columnHooks[$tableName]);
        unset($this->actionHooks[$tableName]);
        unset($this->bulkActionHooks[$tableName]);
        unset($this->filterHooks[$tableName]);
    }
}
