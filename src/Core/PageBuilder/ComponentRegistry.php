<?php

namespace Miran\Mksine\Core\PageBuilder;

use Illuminate\Support\Collection;
use Miran\Mksine\Core\PageBuilder\Contracts\BuilderComponentInterface;

class ComponentRegistry
{
    /**
     * Registered components.
     *
     * @var array<string, class-string<BuilderComponentInterface>>
     */
    protected array $components = [];

    /**
     * Register a component class.
     *
     * @param  class-string<BuilderComponentInterface>  $componentClass
     */
    public function register(string $componentClass): self
    {
        if (! is_subclass_of($componentClass, BuilderComponentInterface::class)) {
            throw new \InvalidArgumentException(
                "Component class must implement BuilderComponentInterface: {$componentClass}"
            );
        }

        $type = $componentClass::getType();
        $this->components[$type] = $componentClass;

        return $this;
    }

    /**
     * Register multiple component classes.
     *
     * @param  array<class-string<BuilderComponentInterface>>  $componentClasses
     */
    public function registerMany(array $componentClasses): self
    {
        foreach ($componentClasses as $componentClass) {
            $this->register($componentClass);
        }

        return $this;
    }

    /**
     * Get a component class by type.
     *
     * @return class-string<BuilderComponentInterface>|null
     */
    public function get(string $type): ?string
    {
        return $this->components[$type] ?? null;
    }

    /**
     * Check if a component type is registered.
     */
    public function has(string $type): bool
    {
        return isset($this->components[$type]);
    }

    /**
     * Get all registered components.
     *
     * @return array<string, class-string<BuilderComponentInterface>>
     */
    public function all(): array
    {
        return $this->components;
    }

    /**
     * Get all components as metadata array (for UI).
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->components as $type => $class) {
            $result[$type] = $class::toArray();
        }

        return $result;
    }

    /**
     * Get components grouped by category.
     */
    public function getByCategory(): Collection
    {
        return collect($this->components)
            ->map(fn ($class) => $class::toArray())
            ->groupBy('category')
            ->map(fn ($items) => $items->values()->all());
    }

    /**
     * Get category metadata.
     */
    public static function getCategoryMeta(): array
    {
        return [
            BaseBuilderComponent::CATEGORY_CONTENT => [
                'name' => __('mksine::page_builder.category_content'),
                'icon' => 'heroicon-o-document-text',
                'order' => 1,
            ],
            BaseBuilderComponent::CATEGORY_MEDIA => [
                'name' => __('mksine::page_builder.category_media'),
                'icon' => 'heroicon-o-photo',
                'order' => 2,
            ],
            BaseBuilderComponent::CATEGORY_LAYOUT => [
                'name' => __('mksine::page_builder.category_layout'),
                'icon' => 'heroicon-o-view-columns',
                'order' => 3,
            ],
            BaseBuilderComponent::CATEGORY_INTERACTIVE => [
                'name' => __('mksine::page_builder.category_interactive'),
                'icon' => 'heroicon-o-cursor-arrow-rays',
                'order' => 4,
            ],
            BaseBuilderComponent::CATEGORY_SECTIONS => [
                'name' => __('mksine::page_builder.category_sections'),
                'icon' => 'heroicon-o-rectangle-stack',
                'order' => 5,
            ],
        ];
    }

    /**
     * Get the Filament schema for a component type.
     */
    public function getSchema(string $type): array
    {
        $class = $this->get($type);

        if (! $class) {
            return [];
        }

        return $class::getSchema();
    }

    /**
     * Create a new component instance.
     */
    public function createInstance(string $type, ?string $id = null): ?array
    {
        $class = $this->get($type);

        if (! $class) {
            return null;
        }

        return $class::createInstance($id);
    }

    /**
     * Validate component data.
     */
    public function validateComponent(string $type, array $data): array
    {
        $class = $this->get($type);

        if (! $class) {
            return $data;
        }

        return $class::validate($data);
    }

    /**
     * Resolve the Blade view name for a block type (registered class or core convention).
     */
    public function resolveRenderView(string $type): string
    {
        $class = $this->get($type);

        if ($class !== null && is_subclass_of($class, BaseBuilderComponent::class)) {
            return $class::getRenderView();
        }

        return 'mksine::page-builder.render.'.$type;
    }
}
