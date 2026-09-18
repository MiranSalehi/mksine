<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

use Filament\Resources\Resource;

/**
 * Manager for extending resources through hooks.
 * Allows listeners to add relations, widgets, and modify resource configuration.
 */
class ResourceHookManager
{
    /**
     * @var array<string, array<callable>>
     */
    private array $relationHooks = [];

    /**
     * @var array<string, array<callable>>
     */
    private array $widgetHooks = [];

    /**
     * Register a hook to add relations to a resource.
     *
     * @param  string  $resourceName  The resource identifier (e.g., 'post.resource')
     * @param  callable  $callback  Callback that receives array of relations and returns modified array
     */
    public function extendRelations(string $resourceName, callable $callback): void
    {
        if (! isset($this->relationHooks[$resourceName])) {
            $this->relationHooks[$resourceName] = [];
        }

        $this->relationHooks[$resourceName][] = $callback;
    }

    /**
     * Register a hook to add widgets to a resource.
     *
     * @param  string  $resourceName  The resource identifier (e.g., 'post.resource')
     * @param  callable  $callback  Callback that receives array of widgets and returns modified array
     */
    public function extendWidgets(string $resourceName, callable $callback): void
    {
        if (! isset($this->widgetHooks[$resourceName])) {
            $this->widgetHooks[$resourceName] = [];
        }

        $this->widgetHooks[$resourceName][] = $callback;
    }

    /**
     * Apply all registered relation hooks.
     *
     * @param  string  $resourceName  The resource identifier
     * @param  array  $relations  The original relations array
     * @return array The modified relations array
     */
    public function applyRelations(string $resourceName, array $relations): array
    {
        if (! isset($this->relationHooks[$resourceName])) {
            return $relations;
        }

        foreach ($this->relationHooks[$resourceName] as $callback) {
            $result = $callback($relations);
            if (is_array($result)) {
                $relations = $result;
            }
        }

        return $relations;
    }

    /**
     * Apply all registered widget hooks.
     *
     * @param  string  $resourceName  The resource identifier
     * @param  array  $widgets  The original widgets array
     * @return array The modified widgets array
     */
    public function applyWidgets(string $resourceName, array $widgets): array
    {
        if (! isset($this->widgetHooks[$resourceName])) {
            return $widgets;
        }

        foreach ($this->widgetHooks[$resourceName] as $callback) {
            $result = $callback($widgets);
            if (is_array($result)) {
                $widgets = $result;
            }
        }

        return $widgets;
    }

    /**
     * Clear all hooks for a resource.
     */
    public function clear(string $resourceName): void
    {
        unset($this->relationHooks[$resourceName]);
        unset($this->widgetHooks[$resourceName]);
    }

    /**
     * @return list<array{name: string, relations: int, widgets: int}>
     */
    public function inspect(): array
    {
        $names = array_values(array_unique(array_merge(
            array_keys($this->relationHooks),
            array_keys($this->widgetHooks),
        )));
        sort($names);

        $rows = [];
        foreach ($names as $name) {
            $rows[] = [
                'name' => $name,
                'relations' => count($this->relationHooks[$name] ?? []),
                'widgets' => count($this->widgetHooks[$name] ?? []),
            ];
        }

        return $rows;
    }
}
