<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\MenuItemSources;

use Miran\Mksine\Contracts\MenuItemSourceInterface;
use Miran\Mksine\Models\MenuItem;
use Miran\Mksine\Models\Post;

/**
 * Post item source for Menu Builder.
 *
 * Allows adding published posts to menus.
 */
class PostMenuItemSource implements MenuItemSourceInterface
{
    public function getKey(): string
    {
        return 'post';
    }

    public function getLabel(): string
    {
        return __('Posts');
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
