<?php

namespace Miran\Mksine\Core\PageBuilder;

use Miran\Mksine\Core\PageBuilder\Contracts\BuilderComponentInterface;

abstract class BaseBuilderComponent implements BuilderComponentInterface
{
    /**
     * Component categories.
     */
    public const CATEGORY_CONTENT = 'content';

    public const CATEGORY_MEDIA = 'media';

    public const CATEGORY_LAYOUT = 'layout';

    public const CATEGORY_INTERACTIVE = 'interactive';

    /**
     * Get the unique type identifier for this component.
     */
    abstract public static function getType(): string;

    /**
     * Get the display name for this component.
     */
    abstract public static function getName(): string;

    /**
     * Get the icon for this component (Heroicon name).
     */
    public static function getIcon(): string
    {
        return 'heroicon-o-cube';
    }

    /**
     * Get the category for this component.
     */
    public static function getCategory(): string
    {
        return self::CATEGORY_CONTENT;
    }

    /**
     * Get the description for this component.
     */
    public static function getDescription(): string
    {
        return '';
    }

    /**
     * Get the schema definition (configurable fields).
     */
    abstract public static function getSchema(): array;

    /**
     * Get the default data for this component.
     */
    public static function getDefaultData(): array
    {
        return [];
    }

    /**
     * Check if this component supports children (nesting).
     */
    public static function supportsChildren(): bool
    {
        return false;
    }

    /**
     * Get the maximum number of children allowed (null = unlimited).
     */
    public static function getMaxChildren(): ?int
    {
        return null;
    }

    /**
     * Validate the component data.
     */
    public static function validate(array $data): array
    {
        return $data;
    }

    /**
     * Get component metadata for the builder UI.
     */
    public static function toArray(): array
    {
        return [
            'type' => static::getType(),
            'name' => static::getName(),
            'icon' => static::getIcon(),
            'category' => static::getCategory(),
            'description' => static::getDescription(),
            'supportsChildren' => static::supportsChildren(),
            'maxChildren' => static::getMaxChildren(),
            'defaultData' => static::getDefaultData(),
        ];
    }

    /**
     * Create a new instance data structure.
     */
    public static function createInstance(?string $id = null): array
    {
        return [
            'id' => $id ?? uniqid('block_'),
            'type' => static::getType(),
            'data' => static::getDefaultData(),
            'children' => static::supportsChildren() ? [] : null,
        ];
    }
}
