<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages\Settings;

use Miran\Mksine\Filament\Settings\GeoSettingsSchema;
use Miran\Mksine\Models\Setting;
use Miran\Mksine\Services\Geo\StoreGeoSettings;

class SettingsGeoPage extends MksSettingsPage
{
    protected static ?int $navigationSort = 15;

    public static function getNavigationLabel(): string
    {
        return __('mksine::geo.settings.tab');
    }

    protected function settingsSchema(): array
    {
        return GeoSettingsSchema::schema();
    }

    protected function loadStoredSettingsIntoForm(): void
    {
        parent::loadStoredSettingsIntoForm();

        $legacyKeys = [
            StoreGeoSettings::ENABLED_COUNTRIES_KEY => 'ecom_enabled_countries',
            StoreGeoSettings::DEFAULT_COUNTRY_KEY => 'ecom_default_checkout_country',
            StoreGeoSettings::ADDRESS_LEVELS_KEY => 'ecom_address_levels',
        ];

        foreach ($legacyKeys as $key => $legacyKey) {
            if (Setting::query()->where('key', $key)->exists()) {
                continue;
            }

            $legacy = $this->readSetting($legacyKey);
            if ($legacy !== null && $legacy !== '') {
                $this->data[$key] = $legacy;
            }
        }
    }
}
