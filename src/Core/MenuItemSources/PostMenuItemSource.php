<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\MenuItemSources;

use Miran\Mksine\Contracts\MenuItemSourcePaginatedInterface;
use Miran\Mksine\Models\MenuItem;
use Miran\Mksine\Models\Post;

/**
 * Post item source for Menu Builder.
 *
 * Allows adding published posts to menus.
 */
class PostMenuItemSource implements MenuItemSourcePaginatedInterface
{
    public function getKey(): string
    {
        return 'post';
    }

    public function getLabel(): string
    {
        return (string) __('mksine::posts.plural_model_label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public function getItems(): array
    {
        return Post::query()
            ->where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn (Post $post) => [
                'id' => $post->id,
                'label' => $post->title,
                'url' => '/post/' . $post->slug,
            ])
            ->toArray();
    }

    /**
     * Paginated items with search (optional; used by Menu Builder for performance).
     *
     * @return array{items: array<int, array{id: int, label: string, url: string}>, total: int}
     */
    public function getItemsPaginated(string $search, int $page, int $perPage): array
    {
        $query = Post::query()
            ->where('status', 'published')
            ->orderBy('published_at', 'desc');

        if ($search !== '') {
            $query->where('title', 'like', '%' . $search . '%');
        }

        $total = $query->count();
        $items = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn (Post $p) => [
                'id' => $p->id,
                'label' => $p->title,
                'url' => '/post/' . $p->slug,
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

        return Post::query()
            ->where('status', 'published')
            ->whereIn('id', $ids)
            ->orderBy('published_at', 'desc')
            ->get()
            ->map(fn (Post $p) => [
                'id' => $p->id,
                'label' => $p->title,
                'url' => '/post/' . $p->slug,
            ])
            ->values()
            ->keyBy('id')
            ->all();
    }

    public function toMenuItem(mixed $item): array
    {
        if ($item instanceof Post) {
            return [
                'type' => MenuItem::TYPE_POST,
                'label' => $item->title,
                'url' => '/post/' . $item->slug,
                'reference_id' => $item->id,
            ];
        }

        return [
            'type' => MenuItem::TYPE_POST,
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
