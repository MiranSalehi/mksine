<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

/**
 * Priority-ordered runtime filters (lower priority runs first).
 *
 * @phpstan-type FilterEntry array{priority: int, callable: callable(mixed ...$args): mixed}
 */
final class HookFilterRegistry
{
    /** @var array<string, list<FilterEntry>> */
    private array $filters = [];

    public function add(string $name, callable $callback, int $priority = 10): void
    {
        if (! isset($this->filters[$name])) {
            $this->filters[$name] = [];
        }
        $this->filters[$name][] = ['priority' => $priority, 'callable' => $callback];
        usort(
            $this->filters[$name],
            static fn (array $a, array $b): int => $a['priority'] <=> $b['priority'],
        );
    }

    /**
     * @template T
     *
     * @param  T  $value
     * @return T
     */
    public function apply(string $name, mixed $value, mixed ...$args): mixed
    {
        if (! isset($this->filters[$name])) {
            return $value;
        }
        foreach ($this->filters[$name] as $entry) {
            $value = $entry['callable']($value, ...$args);
        }

        return $value;
    }
}
