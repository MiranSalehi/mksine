<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Theme;

/**
 * WordPress-style action hooks for theme templates.
 * Plugins/themes can add callables with theme_add_action(); templates fire them with theme_do_action().
 */
class ThemeActionManager
{
    /** @var array<string, array<int, array{priority: int, callable: callable, id?: ?string}>> */
    protected array $actions = [];

    /** @var array<string, array<string, true>> */
    protected array $removedIds = [];

    /**
     * Register a callback for a theme hook. Callbacks are run when theme_do_action($hook) is called.
     * Returned strings are echoed; callbacks can also echo directly.
     *
     * @param  string  $hook  Hook name (e.g. 'home.before_hero', 'home.after_section_latest')
     * @param  callable  $callback  Function that may return HTML string or echo
     * @param  int  $priority  Lower runs first (default 10)
     * @param  string|null  $id  Name for removal; adding the same id replaces the entry in place, and an id already removed from this hook is ignored
     */
    public function addAction(string $hook, callable $callback, int $priority = 10, ?string $id = null): void
    {
        if ($id !== null && isset($this->removedIds[$hook][$id])) {
            return;
        }

        $entry = ['priority' => $priority, 'callable' => $callback, 'id' => $id];
        if ($id !== null) {
            foreach ($this->actions[$hook] ?? [] as $index => $item) {
                if (($item['id'] ?? null) === $id) {
                    $this->actions[$hook][$index] = $entry;

                    return;
                }
            }
        }

        $this->actions[$hook][] = $entry;
    }

    /**
     * Remove callbacks from a hook. A string matches ids first, then string callables, and is
     * remembered as a removed id so a later add with that id is ignored. Any other callable
     * matches by strict identity. Removing something that does not exist does nothing.
     */
    public function removeAction(string $hook, string | callable $handler): void
    {
        if (is_string($handler)) {
            $this->removedIds[$hook][$handler] = true;
        }
        if (! isset($this->actions[$hook])) {
            return;
        }

        $items = $this->actions[$hook];
        $field = is_string($handler) && in_array($handler, array_column($items, 'id'), true) ? 'id' : 'callable';
        $remaining = array_values(array_filter(
            $items,
            static fn (array $item): bool => ($item[$field] ?? null) !== $handler,
        ));

        if ($remaining === []) {
            unset($this->actions[$hook]);

            return;
        }

        $this->actions[$hook] = $remaining;
    }

    public function hasAction(string $hook): bool
    {
        return ($this->actions[$hook] ?? []) !== [];
    }

    /**
     * Run all callbacks registered for the hook. Output is concatenated and returned (and optionally echoed).
     * A callback that throws is reported and its output dropped; the remaining callbacks still run.
     *
     * @param  string  $hook  Hook name
     * @param  array<string, mixed>  $args  Optional arguments, spread as named arguments: each callback must declare matching parameter names or a variadic
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

                try {
                    report($e);
                } catch (\Throwable) {
                    // A broken reporter must not take the page down with it.
                }
                if (config('app.debug')) {
                    $output .= '<!-- Theme hook error [' . e($hook) . ']: ' . e($e->getMessage()) . ' -->';
                }
            }
        }

        return $output;
    }

    /**
     * @return list<array{name: string, callbacks: int, priorities: list<int>}>
     */
    public function inspect(): array
    {
        $rows = [];
        foreach ($this->actions as $hook => $items) {
            $rows[] = [
                'name' => $hook,
                'callbacks' => count($items),
                'priorities' => array_map(
                    static fn (array $item): int => $item['priority'],
                    $items,
                ),
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $a['name'] <=> $b['name']);

        return $rows;
    }
}
