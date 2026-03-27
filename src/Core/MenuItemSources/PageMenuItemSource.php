<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\MenuItemSources;

use Miran\Mksine\Contracts\MenuItemSourcePaginatedInterface;
use Miran\Mksine\Models\MenuItem;
use Miran\Mksine\Models\Page;

/**
 * Page item source for Menu Builder.
 *
 * Allows adding published pages to menus.
 */
class PageMenuItemSource implements MenuItemSourcePaginatedInterface
{
    public function getKey(): string
    {
        return 'page';
    }

    public function getLabel(): string
    {
        return (string) __('mksine::pages.plural_model_label');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-document';
    }

    public function getItems(): array
    {
        return Page::query()
            ->published()
            ->orderBy('title')
            ->limit(100)
            ->get()
            ->map(fn (Page $page) => [
                'id' => $page->id,
                'label' => $page->title,
                'url' => route('pages.show', $page->slug),
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
        $query = Page::query()
            ->published()
            ->orderBy('title');

        if ($search !== '') {
            $query->where('title', 'like', '%' . $search . '%');
        }

        $total = $query->count();
        $items = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn (Page $p) => [
                'id' => $p->id,
                'label' => $p->title,
                'url' => route('pages.show', $p->slug),
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

        return Page::query()
            ->published()
            ->whereIn('id', $ids)
            ->orderBy('title')
            ->get()
            ->map(fn (Page $p) => [
                'id' => $p->id,
                'label' => $p->title,
                'url' => route('pages.show', $p->slug),
            ])
            ->values()
            ->keyBy('id')
            ->all();
    }

    public function toMenuItem(mixed $item): array
    {
        if ($item instanceof Page) {
            return [
                'type' => MenuItem::TYPE_PAGE,
                'label' => $item->title,
                'url' => route('pages.show', $item->slug),
                'reference_id' => $item->id,
            ];
        }

        return [
            'type' => MenuItem::TYPE_PAGE,
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
