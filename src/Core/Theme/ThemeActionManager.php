<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Theme;

/**
 * WordPress-style action hooks for theme templates.
 * Plugins/themes can add callables with theme_add_action(); templates fire them with theme_do_action().
 */
class ThemeActionManager
{
    /** @var array<string, array<int, array{priority: int, callable: callable}>> */
    protected array $actions = [];

    /**
     * Register a callback for a theme hook. Callbacks are run when theme_do_action($hook) is called.
     * Returned strings are echoed; callbacks can also echo directly.
     *
     * @param  string  $hook  Hook name (e.g. 'home.before_hero', 'home.after_section_latest')
     * @param  callable  $callback  Function that may return HTML string or echo
     * @param  int  $priority  Lower runs first (default 10)
     */
    public function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        $this->actions[$hook][] = ['priority' => $priority, 'callable' => $callback];
    }

    /**
     * Run all callbacks registered for the hook. Output is concatenated and returned (and optionally echoed).
     *
     * @param  string  $hook  Hook name
     * @param  array<string, mixed>  $args  Optional arguments passed to each callback
     * @return string Combined output from all callbacks
     */
    public function doAction(string $hook, array $args = []): string
    {
        $callbacks = $this->actions[$hook] ?? [];
        if ($callbacks === []) {
            return '';
        }

        usort($callbacks, fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);

        $output = '';
        foreach ($callbacks as $item) {
            ob_start();
            try {
                $result = $item['callable'](...$args);
                $output .= ob_get_clean();
                if (is_string($result)) {
                    $output .= $result;
                }
            } catch (\Throwable $e) {
                ob_end_clean();
                if (config('app.debug')) {
                    $output .= '<!-- Theme hook error [' . e($hook) . ']: ' . e($e->getMessage()) . ' -->';
                }
            }
        }

        return $output;
    }
}
