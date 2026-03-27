<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\MenuItemSources;

use Miran\Mksine\Contracts\MenuItemSourcePaginatedInterface;
use Miran\Mksine\Models\Category;
use Miran\Mksine\Models\MenuItem;

/**
 * Category item source for Menu Builder.
 *
 * Allows adding categories to menus.
 */
class CategoryMenuItemSource implements MenuItemSourcePaginatedInterface
{
    public function getKey(): string
    {
        return 'category';
    }

    public function getLabel(): string
    {
        return (string) __('mksine::categories.plural_model_label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-tag';
    }

    public function getItems(): array
    {
        return Category::query()
            ->where('is_active', true)
            ->with('parent')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'label' => $category->name,
                'url' => $category->getUrl(),
            ])
            ->toArray();
    }

    /**
     * Only parent categories (parent_id null), each with its direct children.
     * Pagination is over parents; each parent includes a 'children' array for the view.
     *
     * @return array{items: array<int, array{id: int, label: string, url: string, parent_id: null, children: array}>, total: int}
     */
    public function getItemsPaginated(string $search, int $page, int $perPage): array
    {
        $query = Category::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order');

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $total = $query->count();
        $roots = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        $items = $roots->map(fn (Category $root) => $this->categoryToTreeItem($root))->values()->all();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * Build one category as tree item with children loaded recursively.
     */
    private function categoryToTreeItem(Category $category): array
    {
        $children = Category::query()
            ->where('is_active', true)
            ->where('parent_id', $category->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Category $c) => $this->categoryToTreeItem($c))
            ->values()
            ->all();

        return [
            'id' => $category->id,
            'label' => $category->name,
            'url' => $category->getUrl(),
            'parent_id' => $category->parent_id,
            'children' => $children,
        ];
    }

    /**
     * Get items by IDs (optional; used when adding to menu without loading all).
     *
     * @param  array<int>  $ids
     * @return array<int, array{id: int, label: string, url: string}>
     */
    public function getItemsByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return Category::query()
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'label' => $c->name,
                'url' => $c->getUrl(),
                'parent_id' => $c->parent_id,
            ])
            ->values()
            ->keyBy('id')
            ->all();
    }

    public function toMenuItem(mixed $item): array
    {
        if ($item instanceof Category) {
            return [
                'type' => MenuItem::TYPE_CATEGORY,
                'label' => $item->name,
                'url' => $item->getUrl(),
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
