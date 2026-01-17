<?php

declare(strict_types=1);

namespace Miran\Mksine\Contracts;

/**
 * Interface for Menu Item Sources.
 *
 * Implement this interface to create custom item sources
 * that can be added to the Menu Builder UI.
 *
 * Example: Categories, Pages, Posts, Products, etc.
 */
interface MenuItemSourceInterface
{
    /**
     * Get the unique key for this source.
     *
     * Example: 'category', 'page', 'post', 'product'
     */
    public function getKey(): string;

    /**
     * Get the human-readable label for this source.
     *
     * Example: 'Categories', 'Pages', 'Posts', 'Products'
     */
    public function getLabel(): string;

    /**
     * Get the Heroicon name for this source.
     *
     * Example: 'heroicon-o-tag', 'heroicon-o-document-text'
     */
    public function getIcon(): string;

    /**
     * Get all available items that can be added to menus.
     *
     * Return format:
     * [
     *     ['id' => 1, 'label' => 'Technology', 'url' => '/category/technology'],
     *     ['id' => 2, 'label' => 'Sports', 'url' => '/category/sports'],
     * ]
     */
    public function getItems(): array;

    /**
     * Convert a source item to menu item data.
     *
     * @param  mixed  $item  The source item (model or array)
     * @return array{type: string, label: string, url: string|null, reference_id: int|null}
     */
    public function toMenuItem(mixed $item): array;

    /**
     * Get the display form schema for adding items.
     *
     * Return null to use the default checkbox list.
     * Return Filament form components array for custom forms.
     */
    public function getFormSchema(): ?array;

    /**
     * Whether this source supports multiple selection.
     */
    public function supportsMultipleSelection(): bool;
}
