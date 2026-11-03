<?php

declare(strict_types=1);

namespace Miran\Mksine\Services\Geo;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Miran\Mksine\Enums\GeoSource;
use Miran\Mksine\Models\GeoCity;
use Miran\Mksine\Models\GeoCountry;
use Miran\Mksine\Models\GeoState;

final class GeoImportService
{
    private const string DR5HN_BASE = 'https://raw.githubusercontent.com/dr5hn/countries-states-cities-database/master/contributions';

    private const int CITY_CHUNK_SIZE = 500;

    /**
     * @param  string|null  $locationsDatabase  MySQL database to read cities from; null reads the dr5hn per-country JSON files.
     */
    public function __construct(
        private readonly ?string $locationsDatabase = null,
        private readonly string $locationsTable = 'csv-cities',
        private readonly ?GeoImportLogger $logger = null,
    ) {}

    public function importCountries(?callable $progress = null): int
    {
        $payload = $this->fetchRequiredJson(self::DR5HN_BASE . '/countries/countries.json');

        $rows = [];
        foreach ($payload as $country) {
            if (! is_array($country)) {
                continue;
            }

            $iso2 = strtoupper((string) ($country['iso2'] ?? ''));
            if ($iso2 === '') {
                continue;
            }

            $translations = is_array($country['translations'] ?? null) ? $country['translations'] : null;

            $rows[] = [
                'id' => (int) $country['id'],
                'iso2' => $iso2,
                'iso3' => filled($country['iso3'] ?? null) ? strtoupper((string) $country['iso3']) : null,
                'name' => (string) ($country['name'] ?? $iso2),
                'native' => GeoCountryLocaleMap::resolveNativeName(
                    $iso2,
                    is_string($country['native'] ?? null) ? $country['native'] : null,
                    is_string($country['name'] ?? null) ? $country['name'] : null,
                    $translations,
                ),
                'translations' => $translations !== null ? json_encode($translations, JSON_UNESCAPED_UNICODE) : null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $this->upsertChunked('geo_countries', $rows, ['id'], [
            'iso2', 'iso3', 'name', 'native', 'translations', 'is_active', 'updated_at',
        ], 'countries', $progress);
    }

    public function importStates(?string $countryIso2 = null, ?callable $progress = null): int
    {
        $payload = $this->fetchRequiredJson(self::DR5HN_BASE . '/states/states.json');

        $filter = $countryIso2 !== null ? strtoupper($countryIso2) : null;
        $rows = [];

        foreach ($payload as $state) {
            if (! is_array($state)) {
                continue;
            }

            $iso2 = strtoupper((string) ($state['country_code'] ?? ''));
            if ($filter !== null && $iso2 !== $filter) {
                continue;
            }

            $translations = is_array($state['translations'] ?? null) ? $state['translations'] : null;

            $rows[] = [
                'id' => (int) $state['id'],
                'geo_country_id' => (int) $state['country_id'],
                'code' => filled($state['iso2'] ?? null) ? (string) $state['iso2'] : null,
                'name' => (string) ($state['name'] ?? ''),
                'native' => GeoCountryLocaleMap::resolveNativeName(
                    $iso2,
                    is_string($state['native'] ?? null) ? $state['native'] : null,
                    is_string($state['name'] ?? null) ? $state['name'] : null,
                    $translations,
                ),
                'translations' => $translations !== null ? json_encode($translations, JSON_UNESCAPED_UNICODE) : null,
                'source' => GeoSource::Seed->value,
                'is_visible' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        return $this->upsertChunked('geo_states', $rows, ['id'], [
            'geo_country_id', 'code', 'name', 'native', 'translations', 'source', 'is_visible', 'sort_order', 'updated_at',
        ], 'states', $progress);
    }

    public function usesLocationsDatabase(): bool
    {
        return $this->locationsDatabase !== null;
    }

    /**
     * @throws GeoImportException when --locations-database was given but its table cannot be read
     */
    public function assertLocationsDatabaseAvailable(): void
    {
        if (! $this->usesLocationsDatabase()) {
            return;
        }

        try {
            DB::select('SELECT 1 FROM ' . $this->qualifiedLocationsTable() . ' LIMIT 1');
        } catch (\Throwable $exception) {
            throw GeoImportException::locationsDatabaseUnavailable($this->qualifiedLocationsTable(), $exception->getMessage());
        }
    }

    /**
     * Fail early with an actionable message when cities would have no state to belong to.
     *
     * @throws GeoImportException
     */
    public function assertStatesImported(?string $countryIso2): void
    {
        if ($countryIso2 === null) {
            if (! GeoState::query()->exists()) {
                throw GeoImportException::statesMissing(null);
            }

            return;
        }

        $iso2 = strtoupper($countryIso2);

        if (! GeoState::query()->where('geo_country_id', $this->countryIdFor($iso2))->exists()) {
            throw GeoImportException::statesMissing($iso2);
        }
    }

    /**
     * Countries to import cities for: the given one, or every country in geo_countries.
     *
     * @return list<string>
     */
    public function countryCodesForImport(?string $countryIso2): array
    {
        if ($countryIso2 !== null) {
            return [strtoupper($countryIso2)];
        }

        return GeoCountry::query()
            ->orderBy('iso2')
            ->pluck('iso2')
            ->map(static fn (string $iso2): string => strtoupper($iso2))
            ->values()
            ->all();
    }

    /**
     * Upsert one country's cities by dr5hn id. Rows whose state_id is not one of the
     * country's geo_states are skipped and logged instead of failing the whole country.
     *
     * @return array{imported: int, skipped: int}
     *
     * @throws GeoImportException
     */
    public function importCitiesForCountryCode(
        string $countryIso2,
        bool $explicitCountryFilter,
        int $importedSoFar = 0,
        ?callable $progress = null,
    ): array {
        $iso2 = strtoupper($countryIso2);
        $countryId = $this->countryIdFor($iso2);

        /** @var array<int, true> $stateIds */
        $stateIds = GeoState::query()
            ->where('geo_country_id', $countryId)
            ->pluck('id')
            ->mapWithKeys(static fn (int | string $id): array => [(int) $id => true])
            ->all();

        $rows = $this->usesLocationsDatabase()
            ? $this->locationCityRows($iso2, $explicitCountryFilter)
            : $this->dr5hnCityRows($iso2);

        $imported = 0;
        $skipped = 0;
        $missingStateIds = [];
        $buffer = [];

        foreach ($rows as $city) {
            if ($stateIds === []) {
                throw GeoImportException::statesMissing($iso2);
            }

            $stateId = (int) ($city['state_id'] ?? 0);
            if (! isset($stateIds[$stateId])) {
                $skipped++;
                $missingStateIds[$stateId] = true;

                continue;
            }

            $buffer[] = $this->cityRow($city, $iso2, $countryId, $stateId);

            if (count($buffer) >= self::CITY_CHUNK_SIZE) {
                $imported += $this->flushCityBuffer($buffer);
                $buffer = [];
                $this->reportCityProgress($importedSoFar + $imported, $iso2, $progress);
            }
        }

        if ($buffer !== []) {
            $imported += $this->flushCityBuffer($buffer);
            $this->reportCityProgress($importedSoFar + $imported, $iso2, $progress);
        }

        if ($skipped > 0) {
            $ids = array_keys($missingStateIds);
            $shown = implode(', ', array_slice($ids, 0, 10)) . (count($ids) > 10 ? ', …' : '');

            $this->logger?->warning(
                "Country {$iso2} — {$skipped} cities skipped: state_id not in geo_states ({$shown})",
            );
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    private function dr5hnCityRows(string $iso2): \Generator
    {
        $url = self::DR5HN_BASE . '/cities/' . $iso2 . '.json';
        $payload = $this->fetchJson($url);

        if ($payload === null) {
            $this->logger?->info("Country {$iso2} — no city file in dr5hn ({$url})");

            return;
        }

        foreach ($payload as $city) {
            if (is_array($city) && isset($city['id'])) {
                yield $city;
            }
        }

        unset($payload);
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    private function locationCityRows(string $iso2, bool $explicitCountryFilter): \Generator
    {
        $translationIndex = $this->loadCityTranslationIndexForCountry($iso2, $explicitCountryFilter);

        $sql = 'SELECT * FROM ' . $this->qualifiedLocationsTable() . ' WHERE country_code = ? ORDER BY id';

        foreach (DB::cursor($sql, [$iso2]) as $row) {
            $city = (array) $row;
            $city['translations'] = $translationIndex[(int) $city['id']] ?? null;

            yield $city;
        }

        unset($translationIndex);
    }

    /**
     * @param  array<string, mixed>  $city
     * @return array<string, mixed>
     */
    private function cityRow(array $city, string $iso2, int $countryId, int $stateId): array
    {
        $translations = is_array($city['translations'] ?? null) ? $city['translations'] : null;
        $name = is_string($city['name'] ?? null) ? $city['name'] : null;

        return [
            'id' => (int) $city['id'],
            'geo_state_id' => $stateId,
            'geo_country_id' => $countryId,
            'name' => (string) ($name ?? ''),
            'native' => GeoCountryLocaleMap::resolveNativeName(
                $iso2,
                is_string($city['native'] ?? null) ? $city['native'] : null,
                $name,
                $translations,
            ),
            'translations' => $translations !== null ? json_encode($translations, JSON_UNESCAPED_UNICODE) : null,
            'source' => GeoSource::Seed->value,
            'is_visible' => true,
            'latitude' => filled($city['latitude'] ?? null) ? (float) $city['latitude'] : null,
            'longitude' => filled($city['longitude'] ?? null) ? (float) $city['longitude'] : null,
            'timezone' => filled($city['timezone'] ?? null) ? (string) $city['timezone'] : null,
            'wiki_data_id' => filled($city['wikiDataId'] ?? null) ? (string) $city['wikiDataId'] : null,
            'type' => filled($city['type'] ?? null) ? (string) $city['type'] : null,
            'population' => filled($city['population'] ?? null) ? (int) $city['population'] : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function reportCityProgress(int $total, string $iso2, ?callable $progress): void
    {
        $this->logger?->progress('cities', $total, $iso2);
        $progress && $progress($total);
    }

    /**
     * @throws GeoImportException
     */
    private function countryIdFor(string $iso2): int
    {
        $id = GeoCountry::query()->where('iso2', $iso2)->value('id');

        if ($id === null) {
            throw GeoImportException::countryMissing($iso2);
        }

        return (int) $id;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadCityTranslationIndexForCountry(string $countryIso2, bool $explicitCountryFilter): array
    {
        if (! $explicitCountryFilter && GeoCountryLocaleMap::localeForCountry($countryIso2) === null) {
            return [];
        }

        $payload = $this->fetchJson(self::DR5HN_BASE . '/cities/' . $countryIso2 . '.json') ?? [];

        $index = [];
        foreach ($payload as $city) {
            if (! is_array($city) || ! isset($city['id'])) {
                continue;
            }

            $translations = $city['translations'] ?? null;
            if (is_array($translations)) {
                $index[(int) $city['id']] = $translations;
            }
        }

        return $index;
    }

    /**
     * @param  list<array<string, mixed>>  $buffer
     */
    private function flushCityBuffer(array $buffer): int
    {
        GeoCity::query()->upsert($buffer, ['id'], [
            'geo_state_id', 'geo_country_id', 'name', 'native', 'translations', 'source',
            'is_visible', 'latitude', 'longitude', 'timezone', 'wiki_data_id', 'type', 'population', 'updated_at',
        ]);

        return count($buffer);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $uniqueBy
     * @param  list<string>  $update
     */
    private function upsertChunked(
        string $table,
        array $rows,
        array $uniqueBy,
        array $update,
        string $phase,
        ?callable $progress = null,
    ): int {
        $imported = 0;
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($table)->upsert($chunk, $uniqueBy, $update);
            $imported += count($chunk);
            $this->logger?->progress($phase, $imported);
            $progress && $progress($imported);
        }

        return $imported;
    }

    private function qualifiedLocationsTable(): string
    {
        return '`' . $this->locationsDatabase . '`.`' . $this->locationsTable . '`';
    }

    /**
     * @return array<mixed>
     *
     * @throws GeoImportException
     */
    private function fetchRequiredJson(string $url): array
    {
        return $this->fetchJson($url) ?? throw GeoImportException::datasetMissing($url);
    }

    /**
     * @return array<mixed>|null null when the file does not exist (HTTP 404)
     *
     * @throws GeoImportException on network errors, other HTTP errors, or invalid JSON
     */
    private function fetchJson(string $url): ?array
    {
        try {
            $response = Http::timeout(120)->get($url);
        } catch (ConnectionException $exception) {
            throw GeoImportException::downloadFailed($url, $exception->getMessage());
        }

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            throw GeoImportException::downloadFailed($url, 'HTTP ' . $response->status());
        }

        $body = $response->body();
        unset($response);

        $decoded = json_decode($body, true);
        unset($body);

        if (! is_array($decoded)) {
            throw GeoImportException::downloadFailed($url, 'response is not a JSON array');
        }

        return $decoded;
    }
}
