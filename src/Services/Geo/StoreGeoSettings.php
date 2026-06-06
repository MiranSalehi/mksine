<?php

declare(strict_types=1);

namespace Miran\Mksine\Services\Geo;

use Miran\Mksine\Models\GeoCountry;

/**
 * Reads geo preferences from mks settings (core). Ecom and other plugins consume these values.
 */
final class StoreGeoSettings
{
    public const string ENABLED_COUNTRIES_KEY = 'geo_enabled_countries';

    public const string DEFAULT_COUNTRY_KEY = 'geo_default_country';

    public const string ADDRESS_LEVELS_KEY = 'geo_address_levels';

    private const string LEGACY_ENABLED_COUNTRIES_KEY = 'ecom_enabled_countries';

    private const string LEGACY_DEFAULT_COUNTRY_KEY = 'ecom_default_checkout_country';

    private const string LEGACY_ADDRESS_LEVELS_KEY = 'ecom_address_levels';

    /**
     * @return list<string> ISO2 codes
     */
    public function enabledCountryCodes(): array
    {
        $raw = $this->readSetting(self::ENABLED_COUNTRIES_KEY, self::LEGACY_ENABLED_COUNTRIES_KEY);

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $this->normalizeCountryCodes($decoded);
            }
        }

        if (is_array($raw)) {
            return $this->normalizeCountryCodes($raw);
        }

        return [];
    }

    /**
     * @return list<int>
     */
    public function enabledCountryIds(): array
    {
        $codes = $this->enabledCountryCodes();
        if ($codes === []) {
            return GeoCountry::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
        }

        return GeoCountry::query()
            ->whereIn('iso2', $codes)
            ->orderBy('name')
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    public function defaultCheckoutCountryCode(): ?string
    {
        $raw = $this->readSetting(self::DEFAULT_COUNTRY_KEY, self::LEGACY_DEFAULT_COUNTRY_KEY);
        if (! is_string($raw) || trim($raw) === '') {
            $enabled = $this->enabledCountryCodes();

            return $enabled[0] ?? null;
        }

        return strtoupper(trim($raw));
    }

    /**
     * @return array{show_state: bool, show_city: bool}
     */
    public function addressLevelsForCountry(string $iso2): array
    {
        $code = strtoupper(trim($iso2));
        $levels = $this->addressLevels();

        if (isset($levels[$code]) && is_array($levels[$code])) {
            return [
                'show_state' => (bool) ($levels[$code]['show_state'] ?? true),
                'show_city' => (bool) ($levels[$code]['show_city'] ?? true),
            ];
        }

        return [
            'show_state' => true,
            'show_city' => true,
        ];
    }

    public function isStateVisible(string $iso2): bool
    {
        return $this->addressLevelsForCountry($iso2)['show_state'];
    }

    public function isCityVisible(string $iso2): bool
    {
        return $this->addressLevelsForCountry($iso2)['show_city'];
    }

    /**
     * @return array<string, array{show_state?: bool, show_city?: bool}>
     */
    private function addressLevels(): array
    {
        $raw = $this->readSetting(self::ADDRESS_LEVELS_KEY, self::LEGACY_ADDRESS_LEVELS_KEY);

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($raw) ? $raw : [];
    }

    private function readSetting(string $key, string $legacyKey): mixed
    {
        $value = mks_setting($key);

        if ($value !== null && $value !== '') {
            return $value;
        }

        return mks_setting($legacyKey);
    }

    /**
     * @param  list<mixed>  $codes
     * @return list<string>
     */
    private function normalizeCountryCodes(array $codes): array
    {
        $normalized = [];
        foreach ($codes as $code) {
            if (! is_string($code) && ! is_numeric($code)) {
                continue;
            }
            $iso2 = strtoupper(trim((string) $code));
            if (strlen($iso2) === 2) {
                $normalized[] = $iso2;
            }
        }

        return array_values(array_unique($normalized));
    }
}
