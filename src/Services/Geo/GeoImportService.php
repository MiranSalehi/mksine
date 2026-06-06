<?php

declare(strict_types=1);

namespace Miran\Mksine\Services\Geo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Miran\Mksine\Enums\GeoSource;
use Miran\Mksine\Models\GeoCity;
use Miran\Mksine\Models\GeoCountry;
use Miran\Mksine\Models\GeoState;

final class GeoImportService
{
    private const string DR5HN_BASE = 'https://raw.githubusercontent.com/dr5hn/countries-states-cities-database/master/contributions';

    public function __construct(
        private readonly string $locationsDatabase = 'locations',
        private readonly string $locationsTable = 'csv-cities',
    ) {}

    public function importCountries(?callable $progress = null): int
    {
        $payload = $this->fetchJson(self::DR5HN_BASE.'/countries/countries.json');
        if (! is_array($payload)) {
            return 0;
        }

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
        ], $progress);
    }

    public function importStates(?string $countryIso2 = null, ?callable $progress = null): int
    {
        $payload = $this->fetchJson(self::DR5HN_BASE.'/states/states.json');
        if (! is_array($payload)) {
            return 0;
        }

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
        ], $progress);
    }

    public function importCities(?string $countryIso2 = null, ?callable $progress = null): int
    {
        if (! $this->locationsDatabaseAvailable()) {
            return 0;
        }

        $translationIndex = $this->buildCityTranslationIndex($countryIso2);

        $imported = 0;
        $buffer = [];

        foreach ($this->cursorLocationRows($countryIso2) as $row) {
            $iso2 = strtoupper((string) ($row->country_code ?? ''));
            $cityId = (int) $row->id;
            $translations = $translationIndex[$cityId] ?? null;

            $buffer[] = [
                'id' => $cityId,
                'geo_state_id' => (int) $row->state_id,
                'geo_country_id' => (int) $row->country_id,
                'name' => (string) ($row->name ?? ''),
                'native' => GeoCountryLocaleMap::resolveNativeName(
                    $iso2,
                    is_string($row->native ?? null) ? $row->native : null,
                    is_string($row->name ?? null) ? $row->name : null,
                    $translations,
                ),
                'translations' => $translations !== null ? json_encode($translations, JSON_UNESCAPED_UNICODE) : null,
                'source' => GeoSource::Seed->value,
                'is_visible' => true,
                'latitude' => $row->latitude !== null ? (float) $row->latitude : null,
                'longitude' => $row->longitude !== null ? (float) $row->longitude : null,
                'timezone' => filled($row->timezone ?? null) ? (string) $row->timezone : null,
                'wiki_data_id' => filled($row->wikiDataId ?? null) ? (string) $row->wikiDataId : null,
                'type' => filled($row->type ?? null) ? (string) $row->type : null,
                'population' => filled($row->population ?? null) ? (int) $row->population : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($buffer) >= 500) {
                $imported += $this->flushCityBuffer($buffer);
                $buffer = [];
                $progress && $progress($imported);
            }
        }

        if ($buffer !== []) {
            $imported += $this->flushCityBuffer($buffer);
            $progress && $progress($imported);
        }

        return $imported;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildCityTranslationIndex(?string $countryIso2): array
    {
        $index = [];

        if ($countryIso2 !== null) {
            $this->mergeCityTranslationsForCountry($index, strtoupper($countryIso2));

            return $index;
        }

        foreach (GeoCountryLocaleMap::mappedCountryCodes() as $code) {
            $this->mergeCityTranslationsForCountry($index, $code);
        }

        return $index;
    }

    /**
     * @return \Generator<object>
     */
    private function cursorLocationRows(?string $countryIso2): \Generator
    {
        $sql = 'SELECT * FROM '.$this->qualifiedLocationsTable();
        $bindings = [];

        if ($countryIso2 !== null) {
            $sql .= ' WHERE country_code = ?';
            $bindings[] = strtoupper($countryIso2);
        }

        $sql .= ' ORDER BY id';

        foreach (DB::cursor($sql, $bindings) as $row) {
            yield $row;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $index
     */
    private function mergeCityTranslationsForCountry(array &$index, string $iso2): void
    {
        $payload = $this->fetchJson(self::DR5HN_BASE.'/cities/'.$iso2.'.json');
        if (! is_array($payload)) {
            return;
        }

        foreach ($payload as $city) {
            if (! is_array($city) || ! isset($city['id'])) {
                continue;
            }

            $translations = $city['translations'] ?? null;
            if (is_array($translations)) {
                $index[(int) $city['id']] = $translations;
            }
        }
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
    private function upsertChunked(string $table, array $rows, array $uniqueBy, array $update, ?callable $progress = null): int
    {
        $imported = 0;
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table($table)->upsert($chunk, $uniqueBy, $update);
            $imported += count($chunk);
            $progress && $progress($imported);
        }

        return $imported;
    }

    private function locationsDatabaseAvailable(): bool
    {
        try {
            DB::select('SELECT 1 FROM '.$this->qualifiedLocationsTable().' LIMIT 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function qualifiedLocationsTable(): string
    {
        return '`'.$this->locationsDatabase.'`.`'.$this->locationsTable.'`';
    }

    /**
     * @return array<mixed>|null
     */
    private function fetchJson(string $url): ?array
    {
        $response = Http::timeout(120)->get($url);
        if (! $response->successful()) {
            return null;
        }

        $decoded = json_decode($response->body(), true);

        return is_array($decoded) ? $decoded : null;
    }
}
