<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages\Settings;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Miran\Mksine\Filament\Forms\Components\MediaPicker;
use Miran\Mksine\Support\MksDateFormatter;

class SettingsGeneralPage extends MksSettingsPage
{
    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('mksine::settings.tabs.general');
    }

    protected function settingsSchema(): array
    {
        return [
            MediaPicker::make('logo')
                ->inlineLabel(false)
                ->label(__('mksine::settings.logo'))
                ->isRelation(false)
                ->collection('logo')
                ->acceptedFileTypes(['image/*']),
            MediaPicker::make('favicon')
                ->inlineLabel(false)
                ->label(__('mksine::settings.favicon'))
                ->isRelation(false)
                ->collection('favicon')
                ->acceptedFileTypes(['image/*']),
            TextInput::make('site_name')
                ->label(__('mksine::settings.site_name'))
                ->columnSpanFull()
                ->required(),
            TextInput::make('short_site_name')
                ->columnSpanFull()
                ->label(__('mksine::settings.short_site_name')),
            Select::make(MksDateFormatter::SETTING_KEY)
                ->label(__('mksine::settings.date_calendar'))
                ->options([
                    MksDateFormatter::GREGORIAN => __('mksine::settings.date_calendar_gregorian'),
                    MksDateFormatter::SHAMSI => __('mksine::settings.date_calendar_shamsi'),
                ])
                ->default(MksDateFormatter::GREGORIAN)
                ->required()
                ->helperText(__('mksine::settings.date_calendar_helper'))
                ->columnSpanFull(),
        ];
    }
}
