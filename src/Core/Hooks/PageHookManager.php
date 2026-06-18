<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

/**
 * Manager for extending pages through hooks.
 * Allows listeners to add header actions and dashboard widgets to Filament pages.
 */
class PageHookManager
{
    /**
     * @var array<string, array<callable>>
     */
    private array $headerActionHooks = [];

    /**
     * @var array<string, array<callable>>
     */
    private array $widgetHooks = [];

    /**
     * Register a hook to add header actions to a page.
     *
     * @param  string  $pageName  The page identifier (e.g., 'post.list', 'post.edit')
     * @param  callable  $callback  Callback that receives array of actions and returns modified array
     */
    public function extendHeaderActions(string $pageName, callable $callback): void
    {
        if (! isset($this->headerActionHooks[$pageName])) {
            $this->headerActionHooks[$pageName] = [];
        }

        $this->headerActionHooks[$pageName][] = $callback;
    }

    /**
     * Apply all registered header action hooks.
     *
     * @param  string  $pageName  The page identifier
     * @param  array  $actions  The original actions array
     * @return array The modified actions array
     */
    public function applyHeaderActions(string $pageName, array $actions): array
    {
        if (! isset($this->headerActionHooks[$pageName])) {
            return $actions;
        }

        foreach ($this->headerActionHooks[$pageName] as $callback) {
            $result = $callback($actions);
            if (is_array($result)) {
                $actions = $result;
            }
        }

        return $actions;
    }

    /**
     * Register a hook to add or modify widgets on a page (e.g. admin dashboard).
     *
     * @param  string  $pageName  The page identifier (e.g. {@see \Miran\Mksine\Filament\Pages\MksineDashboard::HOOK_NAME})
     * @param  callable  $callback  Callback that receives the widget class list and returns a modified array
     */
    public function extendWidgets(string $pageName, callable $callback): void
    {
        if (! isset($this->widgetHooks[$pageName])) {
            $this->widgetHooks[$pageName] = [];
        }

        $this->widgetHooks[$pageName][] = $callback;
    }

    /**
     * Apply all registered widget hooks for a page.
     *
     * @param  string  $pageName  The page identifier
     * @param  array<class-string|\Filament\Widgets\WidgetConfiguration>  $widgets
     * @return array<class-string|\Filament\Widgets\WidgetConfiguration>
     */
    public function applyWidgets(string $pageName, array $widgets): array
    {
        if (! isset($this->widgetHooks[$pageName])) {
            return $widgets;
        }

        foreach ($this->widgetHooks[$pageName] as $callback) {
            $result = $callback($widgets);
            if (is_array($result)) {
                $widgets = $result;
            }
        }

        return $widgets;
    }

    /**
     * Clear all hooks for a page.
     */
    public function clear(string $pageName): void
    {
        unset($this->headerActionHooks[$pageName]);
        unset($this->widgetHooks[$pageName]);
    }
}
