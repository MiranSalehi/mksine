<?php

declare(strict_types=1);

namespace Miran\Mksine\Services\Geo;

use Illuminate\Support\Collection;
use Miran\Mksine\Models\GeoCity;
use Miran\Mksine\Models\GeoCountry;

final class GeoCityNativeNameSyncService
{
    public function __construct(
        private readonly WikidataFaLabelClient $wikidata,
    ) {}

    /**
     * @return array{updated: int, skipped: int, examined: int, changes: list<array<string, mixed>>}
     */
    public function syncFromWikidata(
        string $countryIso2 = 'IR',
        bool $dryRun = false,
        ?callable $progress = null,
    ): array {
        $country = GeoCountry::query()->where('iso2', strtoupper($countryIso2))->first();
        if ($country === null) {
            return [
                'updated' => 0,
                'skipped' => 0,
                'examined' => 0,
                'changes' => [],
            ];
        }

        $updated = 0;
        $skipped = 0;
        $examined = 0;
        /** @var list<array<string, mixed>> $changes */
        $changes = [];

        GeoCity::query()
            ->where('geo_country_id', $country->id)
            ->whereNotNull('wiki_data_id')
            ->where('wiki_data_id', '!=', '')
            ->with('state')
            ->orderBy('id')
            ->chunkById(200, function (Collection $cities) use (
                $dryRun,
                &$updated,
                &$skipped,
                &$examined,
                &$changes,
                $progress,
            ): void {
                $wikiLabels = $this->wikidata->labelsForIds(
                    $cities->pluck('wiki_data_id')->filter()->all(),
                );

                foreach ($cities as $city) {
                    $examined++;
                    $currentNative = trim((string) ($city->native ?: $city->name));
                    $wikiLabel = $wikiLabels[(string) $city->wiki_data_id] ?? null;

                    if (! is_string($wikiLabel)
                        || $wikiLabel === ''
                        || $wikiLabel === $currentNative
                        || ! GeoCityNativeNameNormalizer::isSafeWikidataRename($currentNative, $wikiLabel)) {
                        $skipped++;
                        $progress && $progress($examined, $updated);

                        continue;
                    }

                    $changes[] = [
                        'id' => (int) $city->id,
                        'from' => $currentNative,
                        'to' => $wikiLabel,
                        'source' => 'wikidata',
                        'state' => $city->state?->native,
                    ];

                    if (! $dryRun) {
                        $city->forceFill(['native' => $wikiLabel])->save();
                    }

                    $updated++;
                    $progress && $progress($examined, $updated);
                }
            });

        return [
            'updated' => $updated,
            'skipped' => $skipped,
            'examined' => $examined,
            'changes' => $changes,
        ];
    }
}
