<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\MenuItemSources;

use Miran\Mksine\Contracts\MenuItemSourcePaginatedInterface;
use Miran\Mksine\Models\MenuItem;
use Miran\Mksine\Models\Tag;

/**
 * Tag item source for Menu Builder.
 *
 * Allows adding flat tags to menus.
 */
class TagMenuItemSource implements MenuItemSourcePaginatedInterface
{
    public function getKey(): string
    {
        return 'tag';
    }

    public function getLabel(): string
    {
        return (string) __('mksine::tags.plural_model_label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-hashtag';
    }

    public function getItems(): array
    {
        return Tag::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'label' => $tag->name,
                'url' => $tag->getUrl(),
            ])
            ->toArray();
    }

    /**
     * Flat pagination over active tags.
     *
     * @return array{items: array<int, array{id: int, label: string, url: string}>, total: int}
     */
    public function getItemsPaginated(string $search, int $page, int $perPage): array
    {
        $query = Tag::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $total = $query->count();
        $items = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'label' => $tag->name,
                'url' => $tag->getUrl(),
            ])
            ->toArray();

        return ['items' => array_values($items), 'total' => $total];
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

        return Tag::query()
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get()
            ->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'label' => $tag->name,
                'url' => $tag->getUrl(),
            ])
            ->values()
            ->keyBy('id')
            ->all();
    }

    public function toMenuItem(mixed $item): array
    {
        if ($item instanceof Tag) {
            return [
                'type' => MenuItem::TYPE_TAG,
                'label' => $item->name,
                'url' => $item->getUrl(),
                'reference_id' => $item->id,
            ];
        }

        return [
            'type' => MenuItem::TYPE_TAG,
            'label' => $item['label'] ?? '',
            'url' => $item['url'] ?? '',
            'reference_id' => $item['id'] ?? null,
        ];
    }

    public function getFormSchema(): ?array
    {
        return null;
    }

    public function supportsMultipleSelection(): bool
    {
        return true;
    }
}
