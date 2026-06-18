<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

/**
 * Helper class for easy access to hook managers.
 * This allows users outside the package to easily register hooks and listeners.
 *
 * Usage examples:
 *
 * // Register a listener for an event
 * Hooks::register('post.creating', MyListener::class, 10);
 *
 * // Extend a form
 * Hooks::extendForm('post.form', function ($schema) {
 *     return $schema->components([...]);
 * });
 *
 * // Extend a table
 * Hooks::extendTable('post.table', function ($table) {
 *     return $table->columns([...]);
 * });
 *
 * // Runtime filter (mutable value passed through callbacks; lower priority runs first)
 * Hooks::addFilter('ecom.checkout.available_payment_methods', function (array $keys, $cart) {
 *     return $keys;
 * });
 * $keys = Hooks::filter('ecom.checkout.available_payment_methods', $keys, $cart);
 */
class Hooks
{
    /**
     * Get the HookManager instance.
     */
    public static function manager(): HookManager
    {
        return app(HookManager::class);
    }

    /**
     * Get the FormHookManager instance.
     */
    public static function formManager(): FormHookManager
    {
        return app(FormHookManager::class);
    }

    /**
     * Get the TableHookManager instance.
     */
    public static function tableManager(): TableHookManager
    {
        return app(TableHookManager::class);
    }

    /**
     * Get the ResourceHookManager instance.
     */
    public static function resourceManager(): ResourceHookManager
    {
        return app(ResourceHookManager::class);
    }

    /**
     * Get the PageHookManager instance.
     */
    public static function pageManager(): PageHookManager
    {
        return app(PageHookManager::class);
    }

    /**
     * Register a listener for an event.
     *
     * @param  string  $eventName  The event name (e.g., 'post.creating')
     * @param  string  $listenerClass  Fully qualified class name of the listener
     * @param  int  $priority  Lower numbers execute first (default: 0)
     */
    public static function register(string $eventName, string $listenerClass, int $priority = 0): void
    {
        static::manager()->register($eventName, $listenerClass, $priority);
    }

    /**
     * Extend a form with additional components.
     *
     * @param  string  $formName  The form identifier (e.g., 'post.form')
     * @param  callable  $callback  Callback that receives Schema and returns modified Schema
     */
    public static function extendForm(string $formName, callable $callback): void
    {
        static::formManager()->extend($formName, $callback);
    }

    /**
     * Extend a table with additional columns, filters, or actions.
     *
     * @param  string  $tableName  The table identifier (e.g., 'post.table')
     * @param  callable  $callback  Callback that receives Table and returns modified Table
     */
    public static function extendTable(string $tableName, callable $callback): void
    {
        static::tableManager()->extend($tableName, $callback);
    }

    /**
     * Enable a listener.
     *
     * @param  string  $listenerClass  Fully qualified class name
     */
    public static function enableListener(string $listenerClass): void
    {
        static::manager()->enableListener($listenerClass);
    }

    /**
     * Disable a listener.
     *
     * @param  string  $listenerClass  Fully qualified class name
     */
    public static function disableListener(string $listenerClass): void
    {
        static::manager()->disableListener($listenerClass);
    }

    /**
     * Set priority for a listener.
     *
     * @param  string  $listenerClass  Fully qualified class name
     * @param  int  $priority  New priority
     */
    public static function setPriority(string $listenerClass, int $priority): void
    {
        static::manager()->setPriority($listenerClass, $priority);
    }

    /**
     * Extend table columns (e.g., make searchable, sortable).
     *
     * @param  string  $tableName  The table identifier (e.g., 'post.table')
     * @param  callable  $callback  Callback that receives array of columns and returns modified array
     */
    public static function extendTableColumns(string $tableName, callable $callback): void
    {
        static::tableManager()->extendColumns($tableName, $callback);
    }

    /**
     * Extend table record actions.
     *
     * @param  string  $tableName  The table identifier
     * @param  callable  $callback  Callback that receives array of actions and returns modified array
     */
    public static function extendTableActions(string $tableName, callable $callback): void
    {
        static::tableManager()->extendActions($tableName, $callback);
    }

    /**
     * Extend table bulk actions.
     *
     * @param  string  $tableName  The table identifier
     * @param  callable  $callback  Callback that receives array of bulk actions and returns modified array
     */
    public static function extendTableBulkActions(string $tableName, callable $callback): void
    {
        static::tableManager()->extendBulkActions($tableName, $callback);
    }

    /**
     * Extend table filters.
     *
     * @param  string  $tableName  The table identifier
     * @param  callable  $callback  Callback that receives array of filters and returns modified array
     */
    public static function extendTableFilters(string $tableName, callable $callback): void
    {
        static::tableManager()->extendFilters($tableName, $callback);
    }

    /**
     * Extend resource relations.
     *
     * @param  string  $resourceName  The resource identifier (e.g., 'post.resource')
     * @param  callable  $callback  Callback that receives array of relations and returns modified array
     */
    public static function extendResourceRelations(string $resourceName, callable $callback): void
    {
        static::resourceManager()->extendRelations($resourceName, $callback);
    }

    /**
     * Extend resource widgets.
     *
     * @param  string  $resourceName  The resource identifier
     * @param  callable  $callback  Callback that receives array of widgets and returns modified array
     */
    public static function extendResourceWidgets(string $resourceName, callable $callback): void
    {
        static::resourceManager()->extendWidgets($resourceName, $callback);
    }

    /**
     * Extend page header actions.
     *
     * @param  string  $pageName  The page identifier (e.g., 'post.list', 'post.edit')
     * @param  callable  $callback  Callback that receives array of actions and returns modified array
     */
    public static function extendPageHeaderActions(string $pageName, callable $callback): void
    {
        static::pageManager()->extendHeaderActions($pageName, $callback);
    }

    /**
     * Extend widgets on a Filament page.
     *
     * @param  string  $pageName  The page identifier (e.g. {@see \Miran\Mksine\Filament\Pages\MksineDashboard::HOOK_NAME})
     * @param  callable  $callback  Callback that receives an array of widget classes and returns a modified array
     */
    public static function extendPageWidgets(string $pageName, callable $callback): void
    {
        static::pageManager()->extendWidgets($pageName, $callback);
    }

    /**
     * Extend admin dashboard widgets ({@see \Miran\Mksine\Filament\Pages\MksineDashboard::HOOK_NAME}).
     *
     * @param  callable  $callback  Callback that receives an array of widget classes and returns a modified array
     */
    public static function extendDashboardWidgets(callable $callback): void
    {
        static::extendPageWidgets(\Miran\Mksine\Filament\Pages\MksineDashboard::HOOK_NAME, $callback);
    }

    /**
     * Register a filter callback. Lower {@code $priority} runs first (default 10).
     */
    public static function addFilter(string $name, callable $callback, int $priority = 10): void
    {
        app(HookFilterRegistry::class)->add($name, $callback, $priority);
    }

    /**
     * Apply all registered filters for {@code $name} to {@code $value}.
     *
     * @template T
     *
     * @param  T  $value
     * @return T
     */
    public static function filter(string $name, mixed $value, mixed ...$args): mixed
    {
        return app(HookFilterRegistry::class)->apply($name, $value, ...$args);
    }
}
