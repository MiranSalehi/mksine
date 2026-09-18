<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\MenuItemSources;

use Miran\Mksine\Contracts\MenuItemSourcePaginatedInterface;
use Miran\Mksine\Core\Content\ContentType;
use Miran\Mksine\Core\Content\ContentTypeRegistry;
use Miran\Mksine\Models\Entry;
use Miran\Mksine\Models\MenuItem;

/**
 * Menu Builder source for published entries of one registered content type.
 */
class EntryMenuItemSource implements MenuItemSourcePaginatedInterface
{
    public function __construct(private string $contentTypeKey) {}

    public function getKey(): string
    {
        return 'entry_'.$this->contentTypeKey;
    }

    public function getLabel(): string
    {
        return $this->type()->pluralLabel;
    }

    public function getIcon(): string
    {
        return $this->type()->icon;
    }

    public function getItems(): array
    {
        return Entry::query()
            ->ofType($this->contentTypeKey)
            ->published()
            ->orderByDesc('published_at')
            ->limit(50)
            ->get()
            ->map(fn (Entry $entry): array => $this->mapEntry($entry))
            ->all();
    }

    /**
     * @return array{items: array<int, array{id: int, label: string, url: string}>, total: int}
     */
    public function getItemsPaginated(string $search, int $page, int $perPage): array
    {
        $query = Entry::query()
            ->ofType($this->contentTypeKey)
            ->published()
            ->orderByDesc('published_at');

        if ($search !== '') {
            $query->where('title', 'like', '%'.$search.'%');
        }

        $total = $query->count();
        $items = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn (Entry $entry): array => $this->mapEntry($entry))
            ->values()
            ->all();

        return ['items' => $items, 'total' => $total];
    }

    /**
     * @param  array<int>  $ids
     * @return array<int, array{id: int, label: string, url: string}>
     */
    public function getItemsByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return Entry::query()
            ->ofType($this->contentTypeKey)
            ->published()
            ->whereIn('id', $ids)
            ->get()
            ->map(fn (Entry $entry): array => $this->mapEntry($entry))
            ->keyBy('id')
            ->all();
    }

    public function toMenuItem(mixed $item): array
    {
        if ($item instanceof Entry) {
            return [
                'type' => $this->getKey(),
                'label' => $item->title,
                'url' => $item->url(),
                'reference_id' => $item->id,
            ];
        }

        return [
            'type' => $this->getKey(),
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

    /**
     * @return array{id: int, label: string, url: string}
     */
    private function mapEntry(Entry $entry): array
    {
        return [
            'id' => $entry->id,
            'label' => $entry->title,
            'url' => $entry->url(),
        ];
    }

    private function type(): ContentType
    {
        return app(ContentTypeRegistry::class)->get($this->contentTypeKey);
    }
}
