<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

/**
 * Manager for extending pages through hooks.
 * Allows listeners to add header actions to resource pages.
 */
class PageHookManager
{
    /**
     * @var array<string, array<callable>>
     */
    private array $headerActionHooks = [];

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
     * Clear all hooks for a page.
     */
    public function clear(string $pageName): void
    {
        unset($this->headerActionHooks[$pageName]);
    }
}
