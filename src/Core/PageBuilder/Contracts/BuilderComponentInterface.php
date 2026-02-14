<?php

namespace Miran\Mksine\Core\PageBuilder\Contracts;

interface BuilderComponentInterface
{
    /**
     * Get the unique type identifier for this component.
     * Example: 'heading', 'text', 'image', 'columns'
     */
    public static function getType(): string;

    /**
     * Get the display name for this component.
     */
    public static function getName(): string;

    /**
     * Get the icon for this component (Heroicon name).
     */
    public static function getIcon(): string;

    /**
     * Get the category for this component.
     * Example: 'content', 'media', 'layout', 'interactive'
     */
    public static function getCategory(): string;

    /**
     * Get the description for this component.
     */
    public static function getDescription(): string;

    /**
     * Get the schema definition (configurable fields).
     * Returns Filament form components.
     */
    public static function getSchema(): array;

    /**
     * Get the default data for this component.
     */
    public static function getDefaultData(): array;

    /**
     * Check if this component supports children (nesting).
     */
    public static function supportsChildren(): bool;

    /**
     * Get the maximum number of children allowed (null = unlimited).
     */
    public static function getMaxChildren(): ?int;

    /**
     * Validate the component data.
     */
    public static function validate(array $data): array;
}
