<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Marketplace;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Miran\Mksine\Support\Marketplace;
use Throwable;

final class MarketplaceCatalogClient
{
    public function index(MarketplaceKind $kind, string $search = '', int $page = 1): MarketplaceCatalogResult
    {
        $page = max(1, $page);
        $query = array_filter([
            'page' => $page > 1 ? $page : null,
            'q' => $search !== '' ? $search : null,
        ], fn (mixed $value): bool => $value !== null);

        try {
            $payload = $this->getJson('/'.$kind->catalogPath(), $query);
        } catch (MarketplaceException $e) {
            return MarketplaceCatalogResult::failed($e->getMessage());
        }

        $items = [];
        foreach ($payload['data'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            try {
                $items[] = MarketplacePackage::fromApi($row, $kind);
            } catch (MarketplaceException) {
                continue;
            }
        }

        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];

        return new MarketplaceCatalogResult(
            items: $items,
            currentPage: (int) ($meta['current_page'] ?? $page),
            nextPage: isset($meta['next_page']) ? (int) $meta['next_page'] : null,
            prevPage: isset($meta['prev_page']) ? (int) $meta['prev_page'] : null,
        );
    }

    public function show(MarketplaceKind $kind, string $slug): MarketplacePackage
    {
        $payload = $this->getJson('/'.$kind->catalogPath().'/'.$slug);
        $data = $payload['data'] ?? null;

        if (! is_array($data)) {
            throw new MarketplaceException(__('mksine::marketplace.listing_unavailable'));
        }

        return MarketplacePackage::fromApi($data, $kind);
    }

    /**
     * @param  array<string, int|string>  $query
     * @return array<string, mixed>
     */
    private function getJson(string $path, array $query = []): array
    {
        try {
            $response = $this->http()
                ->acceptJson()
                ->get(Marketplace::apiUrl().$path, $query)
                ->throw();
        } catch (ConnectionException) {
            throw new MarketplaceException(__('mksine::marketplace.unreachable'));
        } catch (RequestException $e) {
            if ($e->response->notFound()) {
                throw new MarketplaceException(__('mksine::marketplace.listing_unavailable'));
            }

            throw new MarketplaceException(__('mksine::marketplace.unreachable'));
        } catch (Throwable) {
            throw new MarketplaceException(__('mksine::marketplace.unreachable'));
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new MarketplaceException(__('mksine::marketplace.unreachable'));
        }

        return $json;
    }

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout((int) config('mksine.marketplace.timeout', 15))
            ->connectTimeout((int) config('mksine.marketplace.connect_timeout', 5))
            ->retry(2, 250, throw: false)
            ->withUserAgent(Marketplace::userAgent())
            ->withHeaders(['Accept' => 'application/json']);
    }
}
