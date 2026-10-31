<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

/**
 * Priority-ordered runtime filters (lower priority runs first).
 *
 * @phpstan-type FilterEntry array{priority: int, callable: callable(mixed ...$args): mixed, id: ?string}
 */
final class HookFilterRegistry
{
    /** @var array<string, list<FilterEntry>> */
    private array $filters = [];

    /** @var array<string, array<string, true>> */
    private array $removedIds = [];

    /**
     * Register a filter callback. An {@code $id} makes it removable by name: adding the same id
     * again replaces the entry in place, and an id already removed from this hook is ignored.
     */
    public function add(string $name, callable $callback, int $priority = 10, ?string $id = null): void
    {
        if ($id !== null && isset($this->removedIds[$name][$id])) {
            return;
        }
        if (! isset($this->filters[$name])) {
            $this->filters[$name] = [];
        }
        $entry = ['priority' => $priority, 'callable' => $callback, 'id' => $id];
        $index = $id === null ? false : array_search($id, array_column($this->filters[$name], 'id'), true);
        if ($index === false) {
            $this->filters[$name][] = $entry;
        } else {
            $this->filters[$name][$index] = $entry;
        }
        usort(
            $this->filters[$name],
            static fn (array $a, array $b): int => $a['priority'] <=> $b['priority'],
        );
    }

    /**
     * Remove handlers from a hook. A string matches handler ids first, then string callables, and
     * is remembered as a removed id so a later add with that id is ignored. Any other callable
     * matches by strict identity. Removing something that does not exist does nothing.
     */
    public function remove(string $name, string | callable $handler): void
    {
        if (is_string($handler)) {
            $this->removedIds[$name][$handler] = true;
        }
        if (! isset($this->filters[$name])) {
            return;
        }

        $entries = $this->filters[$name];
        $field = is_string($handler) && in_array($handler, array_column($entries, 'id'), true) ? 'id' : 'callable';
        $remaining = array_values(array_filter(
            $entries,
            static fn (array $entry): bool => $entry[$field] !== $handler,
        ));

        if ($remaining === []) {
            unset($this->filters[$name]);

            return;
        }
        $this->filters[$name] = $remaining;
    }

    public function has(string $name): bool
    {
        return ($this->filters[$name] ?? []) !== [];
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

    /**
     * @return list<array{name: string, callbacks: int}>
     */
    public function inspect(): array
    {
        $rows = [];
        foreach ($this->filters as $name => $entries) {
            $rows[] = [
                'name' => $name,
                'callbacks' => count($entries),
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $a['name'] <=> $b['name']);

        return $rows;
    }
}
