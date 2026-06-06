<?php

declare(strict_types=1);

namespace Miran\Mksine\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Miran\Mksine\Services\Geo\GeoResolver;
use Miran\Mksine\Services\Geo\StoreGeoSettings;

final class GeoApiController
{
    public function __construct(
        private readonly GeoResolver $geo,
        private readonly StoreGeoSettings $settings,
    ) {}

    public function countries(Request $request): JsonResponse
    {
        $locale = $request->string('locale')->toString() ?: null;

        $items = $this->geo->enabledCountriesQuery()
            ->get()
            ->map(fn ($country): array => [
                'id' => $country->id,
                'iso2' => $country->iso2,
                'name' => $this->geo->displayName($country, $locale),
                'show_state' => $this->settings->isStateVisible($country->iso2),
                'show_city' => $this->settings->isCityVisible($country->iso2),
            ])
            ->values();

        return response()->json([
            'default_country' => $this->settings->defaultCheckoutCountryCode(),
            'data' => $items,
        ]);
    }

    public function states(Request $request): JsonResponse
    {
        $countryId = (int) $request->query('country_id', 0);
        if ($countryId <= 0) {
            return response()->json(['data' => []]);
        }

        $locale = $request->string('locale')->toString() ?: null;

        $items = $this->geo->statesForCountry($countryId, $locale)
            ->map(fn ($state): array => [
                'id' => $state->id,
                'name' => $this->geo->displayName($state, $locale),
                'code' => $state->code,
            ])
            ->values();

        return response()->json(['data' => $items]);
    }

    public function cities(Request $request): JsonResponse
    {
        $stateId = (int) $request->query('state_id', 0);
        if ($stateId <= 0) {
            return response()->json(['data' => []]);
        }

        $search = $request->string('search')->toString();
        $perPage = min(100, max(10, (int) $request->query('per_page', 50)));
        $locale = $request->string('locale')->toString() ?: null;

        $paginator = $this->geo->citiesForState(
            $stateId,
            $search !== '' ? $search : null,
            $perPage,
        );

        return response()->json([
            'data' => collect($paginator->items())->map(fn ($city): array => [
                'id' => $city->id,
                'name' => $this->geo->displayName($city, $locale),
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
