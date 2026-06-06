<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Settings;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Components\Utilities\Get;
use Miran\Mksine\Models\GeoCountry;
use Miran\Mksine\Services\Geo\StoreGeoSettings;

final class GeoSettingsSchema
{
    /**
     * @return list<\Filament\Schemas\Components\Component>
     */
    public static function schema(): array
    {
        return [
            SchemaSection::make(__('mksine::geo.settings.section'))
                ->description(__('mksine::geo.settings.section_intro'))
                ->schema([
                    Select::make(StoreGeoSettings::ENABLED_COUNTRIES_KEY)
                        ->label(__('mksine::geo.settings.enabled_countries'))
                        ->helperText(__('mksine::geo.settings.enabled_countries_helper'))
                        ->multiple()
                        ->searchable()
                        ->options(static fn (): array => GeoCountry::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'iso2')
                            ->all())
                        ->formatStateUsing(static function ($state): array {
                            if (is_array($state)) {
                                return array_values(array_map(static fn ($code): string => strtoupper((string) $code), $state));
                            }
                            if (is_string($state) && $state !== '') {
                                $decoded = json_decode($state, true);

                                return is_array($decoded)
                                    ? array_values(array_map(static fn ($code): string => strtoupper((string) $code), $decoded))
                                    : [];
                            }

                            return [];
                        })
                        ->dehydrateStateUsing(static fn ($state): string => json_encode(
                            array_values(array_unique(array_filter(
                                array_map(static fn ($code): string => strtoupper(trim((string) $code)), is_array($state) ? $state : []),
                                static fn (string $code): bool => strlen($code) === 2,
                            ))),
                        ))
                        ->columnSpanFull(),
                    Select::make(StoreGeoSettings::DEFAULT_COUNTRY_KEY)
                        ->label(__('mksine::geo.settings.default_country'))
                        ->helperText(__('mksine::geo.settings.default_country_helper'))
                        ->searchable()
                        ->options(static function (Get $get): array {
                            $enabled = $get(StoreGeoSettings::ENABLED_COUNTRIES_KEY);
                            if (is_string($enabled)) {
                                $enabled = json_decode($enabled, true);
                            }
                            $codes = is_array($enabled) ? $enabled : [];

                            $query = GeoCountry::query()->where('is_active', true)->orderBy('name');
                            if ($codes !== []) {
                                $query->whereIn('iso2', array_map(static fn ($c): string => strtoupper((string) $c), $codes));
                            }

                            return $query->pluck('name', 'iso2')->all();
                        })
                        ->columnSpanFull(),
                    Repeater::make(StoreGeoSettings::ADDRESS_LEVELS_KEY)
                        ->label(__('mksine::geo.settings.address_levels'))
                        ->helperText(__('mksine::geo.settings.address_levels_helper'))
                        ->schema([
                            Select::make('country')
                                ->label(__('mksine::geo.settings.address_level_country'))
                                ->required()
                                ->searchable()
                                ->options(static fn (): array => GeoCountry::query()
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'iso2')
                                    ->all()),
                            Toggle::make('show_state')
                                ->label(__('mksine::geo.settings.address_level_show_state'))
                                ->default(true),
                            Toggle::make('show_city')
                                ->label(__('mksine::geo.settings.address_level_show_city'))
                                ->default(true),
                        ])
                        ->columns(3)
                        ->formatStateUsing(static function ($state): array {
                            if (is_array($state) && array_is_list($state)) {
                                return $state;
                            }

                            $levels = is_array($state) ? $state : [];
                            $rows = [];
                            foreach ($levels as $iso2 => $config) {
                                if (! is_array($config)) {
                                    continue;
                                }
                                $rows[] = [
                                    'country' => strtoupper((string) $iso2),
                                    'show_state' => (bool) ($config['show_state'] ?? true),
                                    'show_city' => (bool) ($config['show_city'] ?? true),
                                ];
                            }

                            return $rows;
                        })
                        ->dehydrateStateUsing(static function ($state): array {
                            $map = [];
                            foreach (is_array($state) ? $state : [] as $row) {
                                if (! is_array($row)) {
                                    continue;
                                }
                                $code = strtoupper(trim((string) ($row['country'] ?? '')));
                                if (strlen($code) !== 2) {
                                    continue;
                                }
                                $map[$code] = [
                                    'show_state' => (bool) ($row['show_state'] ?? true),
                                    'show_city' => (bool) ($row['show_city'] ?? true),
                                ];
                            }

                            return $map;
                        })
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->columnSpanFull(),
        ];
    }
}
