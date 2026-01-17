<?php

declare(strict_types=1);

namespace Miran\Mksine\Services;

use Miran\Mksine\Models\Menu;
use Miran\Mksine\Models\MenuLocation;

/**
 * Service for retrieving menus and menu trees.
 *
 * This is the public API for frontend developers to access menus
 * without needing to know the internal structure.
 */
class MenuService
{
    /**
     * Get the menu tree for a specific location.
     *
     * @return array|null The nested menu tree, or null if no menu assigned
     */
    public function forLocation(string $key): ?array
    {
        $menu = Menu::forLocation($key);

        if (! $menu) {
            return null;
        }

        return $this->buildTree($menu);
    }

    /**
     * Get a menu tree by its slug.
     *
     * @return array|null The nested menu tree, or null if menu not found
     */
    public function getMenuTree(string $slug): ?array
    {
        $menu = Menu::where('slug', $slug)->first();

        if (! $menu) {
            return null;
        }

        return $this->buildTree($menu);
    }

    /**
     * Get a menu tree by its ID.
     *
     * @return array|null The nested menu tree, or null if menu not found
     */
    public function getMenuById(int $id): ?array
    {
        $menu = Menu::find($id);

        if (! $menu) {
            return null;
        }

        return $this->buildTree($menu);
    }

    /**
     * Build the tree structure for a menu.
     */
    public function buildTree(Menu $menu): array
    {
        return [
            'id' => $menu->id,
            'name' => $menu->name,
            'slug' => $menu->slug,
            'items' => $menu->getTree(),
        ];
    }

    /**
     * Get all menus.
     *
     * @return array<Menu>
     */
    public function getAllMenus(): array
    {
        return Menu::orderBy('name')->get()->toArray();
    }

    /**
     * Get all locations with their assigned menus.
     */
    public function getLocationsWithMenus(): array
    {
        return MenuLocation::with('menu')
            ->orderBy('label')
            ->get()
            ->map(fn (MenuLocation $location) => [
                'id' => $location->id,
                'key' => $location->key,
                'label' => $location->label,
                'menu' => $location->menu?->only(['id', 'name', 'slug']),
            ])
            ->toArray();
    }
}
