<?php

declare(strict_types=1);

namespace Miran\Mksine\Contracts;

/**
 * Optional interface for menu item sources that support pagination and search.
 *
 * When implemented, the Menu Builder will use getItemsPaginated() and getItemsByIds()
 * instead of loading all items via getItems(), improving performance for large lists.
 *
 * Sources that only implement MenuItemSourceInterface still work; they get
 * in-memory pagination/filtering over getItems().
 */
interface MenuItemSourcePaginatedInterface extends MenuItemSourceInterface
{
    /**
     * Return a page of items with optional search.
     * Items may include optional 'parent_id' (int|null) for hierarchical display in the UI.
     *
     * @return array{items: array<int, array{id: int, label: string, url: string, parent_id?: int|null}>, total: int}
     */
    public function getItemsPaginated(string $search, int $page, int $perPage): array;

    /**
     * Get items by IDs (e.g. when adding selected items to the menu).
     *
     * @param  array<int>  $ids
     * @return array<int, array{id: int, label: string, url: string}>
     */
    public function getItemsByIds(array $ids): array;
}
