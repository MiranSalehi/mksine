<?php

namespace Miran\Mksine\Core\PageBuilder;

use Illuminate\Support\Collection;

class TemplateRegistry
{
    /**
     * Registered templates.
     *
     * @var array<string, array>
     */
    protected array $templates = [];

    /**
     * Register a template.
     */
    public function register(string $key, array $config): self
    {
        $this->templates[$key] = $config;

        return $this;
    }

    /**
     * Get all templates.
     */
    public function all(): Collection
    {
        return collect($this->templates);
    }

    /**
     * Get templates grouped by category.
     */
    public function byCategory(): Collection
    {
        $result = [];
        foreach ($this->templates as $key => $config) {
            $cat = $config['category'] ?? 'General';
            if (! isset($result[$cat])) {
                $result[$cat] = [];
            }
            $result[$cat][$key] = $config;
        }

        return collect($result);
    }

    /**
     * Get a specific template by key.
     */
    public function get(string $key): ?array
    {
        return $this->templates[$key] ?? null;
    }

    /**
     * Get template blocks (ready to load into PageBuilder).
     */
    public function getBlocks(string $key): array
    {
        $template = $this->get($key);

        if (! $template || ! isset($template['blocks'])) {
            return [];
        }

        return $this->regenerateIds($template['blocks']);
    }

    /**
     * Regenerate block IDs to ensure uniqueness.
     */
    protected function regenerateIds(array $blocks): array
    {
        return array_map(function ($block) {
            $block['id'] = 'block-'.uniqid();

            if (! empty($block['children'])) {
                $block['children'] = $this->regenerateColumnIds($block['children']);
            }

            return $block;
        }, $blocks);
    }

    /**
     * Regenerate IDs for column children (columns have array of { id, items: [blocks] }).
     */
    protected function regenerateColumnIds(array $columns): array
    {
        return array_map(function ($column) {
            $column['id'] = 'col-'.uniqid();
            if (! empty($column['items'])) {
                $column['items'] = $this->regenerateIds($column['items']);
            }

            return $column;
        }, $columns);
    }
}
