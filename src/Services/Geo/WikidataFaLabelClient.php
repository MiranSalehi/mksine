<?php

declare(strict_types=1);

namespace Miran\Mksine\Services\Geo;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

final class WikidataFaLabelClient
{
    private const int CHUNK_SIZE = 50;

    /**
     * @param  list<string>  $wikiDataIds
     * @return array<string, string> map wikiDataId => fa label
     */
    public function labelsForIds(array $wikiDataIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn (mixed $id): string => trim((string) $id),
            $wikiDataIds,
        ))));

        if ($ids === []) {
            return [];
        }

        $labels = [];

        foreach (array_chunk($ids, self::CHUNK_SIZE) as $chunk) {
            $response = $this->request()->get('https://www.wikidata.org/w/api.php', [
                'action' => 'wbgetentities',
                'ids' => implode('|', $chunk),
                'props' => 'labels',
                'languages' => 'fa',
                'format' => 'json',
            ]);

            if (! $response->successful()) {
                continue;
            }

            $entities = $response->json('entities');
            if (! is_array($entities)) {
                continue;
            }

            foreach ($entities as $wikiDataId => $entity) {
                if (! is_string($wikiDataId) || ! is_array($entity)) {
                    continue;
                }

                $value = $entity['labels']['fa']['value'] ?? null;
                if (is_string($value) && trim($value) !== '') {
                    $labels[$wikiDataId] = trim($value);
                }
            }
        }

        return $labels;
    }

    private function request(): PendingRequest
    {
        return Http::timeout(30)
            ->retry(2, 500)
            ->withHeaders([
                'User-Agent' => 'MksineGeoCitySync/1.0 (+https://mksine.test)',
            ]);
    }
}
