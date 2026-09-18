<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Marketplace;

final readonly class MarketplaceCatalogResult
{
    /**
     * @param  list<MarketplacePackage>  $items
     */
    public function __construct(
        public array $items,
        public int $currentPage,
        public ?int $nextPage,
        public ?int $prevPage,
        public bool $ok = true,
        public ?string $error = null,
    ) {}

    public static function failed(string $error): self
    {
        return new self(
            items: [],
            currentPage: 1,
            nextPage: null,
            prevPage: null,
            ok: false,
            error: $error,
        );
    }

    /**
     * @return array{ok: bool, error: string|null, current_page: int, next_page: int|null, prev_page: int|null, items: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'error' => $this->error,
            'current_page' => $this->currentPage,
            'next_page' => $this->nextPage,
            'prev_page' => $this->prevPage,
            'items' => array_map(
                static fn (MarketplacePackage $item): array => $item->toArray(),
                $this->items,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromSnapshot(array $data, MarketplaceKind $kind): self
    {
        if (($data['ok'] ?? true) === false) {
            return self::failed((string) ($data['error'] ?? ''));
        }

        $items = [];
        foreach ($data['items'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            try {
                $items[] = MarketplacePackage::fromApi($row, $kind);
            } catch (MarketplaceException) {
                continue;
            }
        }

        return new self(
            items: $items,
            currentPage: (int) ($data['current_page'] ?? 1),
            nextPage: isset($data['next_page']) ? (int) $data['next_page'] : null,
            prevPage: isset($data['prev_page']) ? (int) $data['prev_page'] : null,
        );
    }
}
