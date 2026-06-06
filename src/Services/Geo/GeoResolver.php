<?php

declare(strict_types=1);

namespace Miran\Mksine\Services\Geo;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
use Miran\Mksine\Models\GeoCity;
use Miran\Mksine\Models\GeoCountry;
use Miran\Mksine\Models\GeoState;

final class GeoResolver
{
    public function __construct(
        private readonly StoreGeoSettings $settings,
    ) {}

    public static function make(): self
    {
        return app(self::class);
    }

    /**
     * @return Builder<GeoCountry>
     */
    public function enabledCountriesQuery(): Builder
    {
        $ids = $this->settings->enabledCountryIds();

        return GeoCountry::query()
            ->where('is_active', true)
            ->when($ids !== [], fn (Builder $q) => $q->whereIn('id', $ids))
            ->orderBy('name');
    }

    /**
     * @return list<int>
     */
    public function enabledCountryIds(): array
    {
        return $this->settings->enabledCountryIds();
    }

    /**
     * @return array<int, string> id => label
     */
    public function countriesForSelect(?string $locale = null): array
    {
        return $this->enabledCountriesQuery()
            ->get()
            ->mapWithKeys(fn (GeoCountry $country): array => [
                $country->id => $this->displayName($country, $locale),
            ])
            ->all();
    }

    /**
     * @return Collection<int, GeoState>
     */
    public function statesForCountry(int $countryId, ?string $locale = null): Collection
    {
        if (! in_array($countryId, $this->enabledCountryIds(), true)) {
            return new Collection;
        }

        return GeoState::query()
            ->where('geo_country_id', $countryId)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function statesForSelect(int $countryId, ?string $locale = null): array
    {
        return $this->statesForCountry($countryId, $locale)
            ->mapWithKeys(fn (GeoState $state): array => [
                $state->id => $this->displayName($state, $locale),
            ])
            ->all();
    }

    public function citiesForState(int $stateId, ?string $search = null, int $perPage = 50): LengthAwarePaginator
    {
        $state = GeoState::query()->find($stateId);
        if ($state === null || ! in_array((int) $state->geo_country_id, $this->enabledCountryIds(), true)) {
            return GeoCity::query()->whereRaw('0 = 1')->paginate($perPage);
        }

        $query = GeoCity::query()
            ->where('geo_state_id', $stateId)
            ->where('is_visible', true)
            ->orderBy('name');

        if (is_string($search) && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('name', 'like', $term)
                    ->orWhere('native', 'like', $term);
            });
        }

        return $query->paginate($perPage);
    }

    public function displayName(GeoCountry|GeoState|GeoCity $entity, ?string $locale = null): string
    {
        $locale ??= App::getLocale();
        $translations = $entity->translations;

        if (is_array($translations) && isset($translations[$locale]) && is_string($translations[$locale])) {
            $fromLocale = trim($translations[$locale]);
            if ($fromLocale !== '') {
                return $fromLocale;
            }
        }

        if (is_string($entity->native) && trim($entity->native) !== '') {
            return trim($entity->native);
        }

        return trim((string) $entity->name);
    }

    public function isStateVisible(int $countryId): bool
    {
        $iso2 = GeoCountry::query()->whereKey($countryId)->value('iso2');

        return is_string($iso2) ? $this->settings->isStateVisible($iso2) : true;
    }

    public function isCityVisible(int $countryId): bool
    {
        $iso2 = GeoCountry::query()->whereKey($countryId)->value('iso2');

        return is_string($iso2) ? $this->settings->isCityVisible($iso2) : true;
    }

    public function resolveCountryByIso2(string $iso2): ?GeoCountry
    {
        $code = strtoupper(trim($iso2));

        return $this->enabledCountriesQuery()
            ->where('iso2', $code)
            ->first();
    }

    public function snapshotAddressLabels(
        ?int $countryId,
        ?int $stateId,
        ?int $cityId,
        ?string $locale = null,
    ): array {
        $country = $countryId ? GeoCountry::query()->find($countryId) : null;
        $state = $stateId ? GeoState::query()->find($stateId) : null;
        $city = $cityId ? GeoCity::query()->find($cityId) : null;

        return [
            'country' => $country ? $country->iso2 : '',
            'country_name' => $country ? $this->displayName($country, $locale) : '',
            'region' => $state ? $this->displayName($state, $locale) : '',
            'city' => $city ? $this->displayName($city, $locale) : '',
        ];
    }
}
