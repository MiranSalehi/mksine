<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\MenuItemSources;

use Miran\Mksine\Contracts\MenuItemSourceInterface;
use Miran\Mksine\Models\Category;
use Miran\Mksine\Models\MenuItem;

/**
 * Category item source for Menu Builder.
 *
 * Allows adding categories to menus.
 */
class CategoryMenuItemSource implements MenuItemSourceInterface
{
    public function getKey(): string
    {
        return 'category';
    }

    public function getLabel(): string
    {
        return __('Categories');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-tag';
    }

    public function getItems(): array
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'label' => $category->name,
                'url' => '/category/' . $category->slug,
            ])
            ->toArray();
    }

    public function toMenuItem(mixed $item): array
    {
        if ($item instanceof Category) {
            return [
                'type' => MenuItem::TYPE_CATEGORY,
                'label' => $item->name,
                'url' => '/category/' . $item->slug,
                'reference_id' => $item->id,
            ];
        }

        // If it's an array (from getItems)
        return [
            'type' => MenuItem::TYPE_CATEGORY,
            'label' => $item['label'] ?? '',
            'url' => $item['url'] ?? '',
            'reference_id' => $item['id'] ?? null,
        ];
    }

    public function getFormSchema(): ?array
    {
        return null; // Use default checkbox list
    }

    public function supportsMultipleSelection(): bool
    {
        return true;
    }
}
