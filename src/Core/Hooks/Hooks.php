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
 *
 * // Register a storefront shortcode
 * Hooks::addShortcode('gallery', GalleryShortcode::class, 10);
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
     * Insert component(s) before a named form field or section anchor.
     *
     * @param  callable  $callback  fn ($original): Component|array
     */
    public static function beforeFormComponent(
        string $formName,
        string $anchor,
        callable $callback,
        int $priority = 0,
    ): void {
        static::formManager()->extendSlot($formName, 'before', $anchor, $callback, $priority);
    }

    /**
     * Insert component(s) after a named form field or section anchor.
     *
     * @param  callable  $callback  fn ($original): Component|array
     */
    public static function afterFormComponent(
        string $formName,
        string $anchor,
        callable $callback,
        int $priority = 0,
    ): void {
        static::formManager()->extendSlot($formName, 'after', $anchor, $callback, $priority);
    }

    /**
     * Replace or hide a named form field or section anchor.
     * Return null or [] to hide. Last registered (highest priority) wins.
     *
     * @param  callable  $callback  fn ($original): Component|array|null
     */
    public static function replaceFormComponent(
        string $formName,
        string $anchor,
        callable $callback,
        int $priority = 0,
    ): void {
        static::formManager()->extendSlot($formName, 'replace', $anchor, $callback, $priority);
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

    /**
     * Register a storefront shortcode handler. Lower {@code $priority} runs first when multiple handlers share a tag.
     */
    public static function addShortcode(
        string $tag,
        callable|string $handler,
        int $priority = 10,
        ?\Miran\Mksine\Core\Shortcodes\ShortcodeCatalogEntry $catalog = null,
    ): void {
        app(\Miran\Mksine\Core\Shortcodes\ShortcodeRegistry::class)->add(
            $tag,
            $handler,
            $priority,
            livewire: false,
            catalog: $catalog,
        );
    }

    /**
     * Register a shortcode that mounts a Livewire component (not cacheable at render time).
     *
     * @param  class-string  $componentClass
     * @param  array<string, mixed>  $defaultParams
     */
    public static function addLivewireShortcode(
        string $tag,
        string $componentClass,
        array $defaultParams = [],
        int $priority = 10,
        ?\Miran\Mksine\Core\Shortcodes\ShortcodeCatalogEntry $catalog = null,
    ): void {
        app(\Miran\Mksine\Core\Shortcodes\ShortcodeRegistry::class)->add(
            $tag,
            function (array $attrs, ?string $content, \Miran\Mksine\Core\Shortcodes\ShortcodeContext $context) use ($componentClass, $defaultParams): string {
                $params = array_merge($defaultParams, $attrs);

                return \Miran\Mksine\Support\Shortcodes\ShortcodeLivewire::mount($componentClass, $params);
            },
            $priority,
            livewire: true,
            catalog: $catalog,
        );
    }
}
