<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Miran\Mksine\Models\GeoCity;
use Miran\Mksine\Models\GeoCountry;
use Miran\Mksine\Models\GeoState;

class GeoMigrateLegacyIranCommand extends Command
{
    protected $signature = 'mks:geo:migrate-legacy-iran';

    protected $description = 'Migrate legacy mks_ecom_iran_* address/shipping data to geo_* tables';

    public function handle(): int
    {
        if (! Schema::hasTable('mks_ecom_iran_provinces')) {
            $this->info('No legacy Iran geo tables found. Skipping.');

            return self::SUCCESS;
        }

        $iran = GeoCountry::query()->where('iso2', 'IR')->first();
        if ($iran === null) {
            $this->error('Iran (IR) not found in geo_countries. Run mks:geo:import first.');

            return self::FAILURE;
        }

        $provinceMap = $this->buildProvinceMap($iran->id);
        $cityMap = $this->buildCityMap($iran->id, $provinceMap);

        $this->migrateCustomerAddresses($provinceMap, $cityMap, $iran->id);
        $this->migrateShippingMethods($provinceMap, $cityMap);
        $this->migrateShippingZoneLocations($iran->id, $provinceMap);

        $this->info('Legacy Iran geo migration completed.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, int> old province id => geo_state id
     */
    private function buildProvinceMap(int $iranCountryId): array
    {
        $map = [];
        $legacyProvinces = DB::table('mks_ecom_iran_provinces')->orderBy('id')->get();

        foreach ($legacyProvinces as $legacy) {
            $state = GeoState::query()
                ->where('geo_country_id', $iranCountryId)
                ->where(function ($q) use ($legacy): void {
                    $name = (string) $legacy->name;
                    $q->where('native', $name)->orWhere('name', $name);
                })
                ->first();

            if ($state !== null) {
                $map[(int) $legacy->id] = (int) $state->id;
            }
        }

        return $map;
    }

    /**
     * @param  array<int, int>  $provinceMap
     * @return array<int, int> old city id => geo_city id
     */
    private function buildCityMap(int $iranCountryId, array $provinceMap): array
    {
        $map = [];
        $legacyCities = DB::table('mks_ecom_iran_cities')->orderBy('id')->get();

        foreach ($legacyCities as $legacy) {
            $geoStateId = $provinceMap[(int) $legacy->province_id] ?? null;
            if ($geoStateId === null) {
                continue;
            }

            $city = GeoCity::query()
                ->where('geo_country_id', $iranCountryId)
                ->where('geo_state_id', $geoStateId)
                ->where(function ($q) use ($legacy): void {
                    $name = (string) $legacy->name;
                    $q->where('native', $name)->orWhere('name', $name);
                })
                ->first();

            if ($city !== null) {
                $map[(int) $legacy->id] = (int) $city->id;
            }
        }

        return $map;
    }

    /**
     * @param  array<int, int>  $provinceMap
     * @param  array<int, int>  $cityMap
     */
    private function migrateCustomerAddresses(array $provinceMap, array $cityMap, int $iranCountryId): void
    {
        if (! Schema::hasColumn('mks_ecom_customer_addresses', 'geo_country_id')) {
            return;
        }

        DB::table('mks_ecom_customer_addresses')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($provinceMap, $cityMap, $iranCountryId): void {
                foreach ($rows as $row) {
                    $updates = [];
                    $legacyProvince = (int) ($row->iran_province_id ?? 0);
                    $legacyCity = (int) ($row->iran_city_id ?? 0);

                    if ($legacyProvince > 0 && isset($provinceMap[$legacyProvince])) {
                        $updates['geo_state_id'] = $provinceMap[$legacyProvince];
                        $updates['geo_country_id'] = $iranCountryId;
                    }
                    if ($legacyCity > 0 && isset($cityMap[$legacyCity])) {
                        $updates['geo_city_id'] = $cityMap[$legacyCity];
                        $updates['geo_country_id'] = $iranCountryId;
                    }

                    if ($updates !== []) {
                        DB::table('mks_ecom_customer_addresses')
                            ->where('id', $row->id)
                            ->update($updates);
                    }
                }
            });
    }

    /**
     * @param  array<int, int>  $provinceMap
     * @param  array<int, int>  $cityMap
     */
    private function migrateShippingMethods(array $provinceMap, array $cityMap): void
    {
        if (! Schema::hasColumn('mks_ecom_shipping_methods', 'geo_scope')) {
            return;
        }

        foreach (DB::table('mks_ecom_shipping_methods')->orderBy('id')->cursor() as $method) {
            $legacy = json_decode((string) ($method->iran_geo ?? ''), true);
            if (! is_array($legacy)) {
                continue;
            }

            $stateIds = [];
            foreach ((array) ($legacy['province_ids'] ?? []) as $oldId) {
                $mapped = $provinceMap[(int) $oldId] ?? null;
                if ($mapped !== null) {
                    $stateIds[] = $mapped;
                }
            }

            $cityIds = [];
            foreach ((array) ($legacy['city_ids'] ?? []) as $oldId) {
                $mapped = $cityMap[(int) $oldId] ?? null;
                if ($mapped !== null) {
                    $cityIds[] = $mapped;
                }
            }

            DB::table('mks_ecom_shipping_methods')
                ->where('id', $method->id)
                ->update([
                    'geo_scope' => json_encode([
                        'state_ids' => array_values(array_unique($stateIds)),
                        'city_ids' => array_values(array_unique($cityIds)),
                    ]),
                ]);
        }
    }

    /**
     * @param  array<int, int>  $provinceMap
     */
    private function migrateShippingZoneLocations(int $iranCountryId, array $provinceMap): void
    {
        if (! Schema::hasColumn('mks_ecom_shipping_zone_locations', 'geo_country_id')) {
            return;
        }

        foreach (DB::table('mks_ecom_shipping_zone_locations')->orderBy('id')->cursor() as $location) {
            $countryCode = strtoupper(trim((string) ($location->country ?? '')));
            $updates = [];

            if ($countryCode !== '') {
                $country = GeoCountry::query()->where('iso2', $countryCode)->first();
                if ($country !== null) {
                    $updates['geo_country_id'] = $country->id;
                }
            }

            $stateName = trim((string) ($location->state ?? ''));
            if ($stateName !== '' && $countryCode === 'IR') {
                $state = GeoState::query()
                    ->where('geo_country_id', $iranCountryId)
                    ->where(function ($q) use ($stateName): void {
                        $q->where('name', $stateName)
                            ->orWhere('native', $stateName);
                    })
                    ->first();
                if ($state !== null) {
                    $updates['geo_state_id'] = $state->id;
                }
            }

            if ($updates !== []) {
                DB::table('mks_ecom_shipping_zone_locations')
                    ->where('id', $location->id)
                    ->update($updates);
            }
        }
    }
}
