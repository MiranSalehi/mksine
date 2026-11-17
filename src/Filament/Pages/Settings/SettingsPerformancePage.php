<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages\Settings;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Miran\Mksine\Core\PageCache\PageCache;

/**
 * Speed settings: the guest page cache switch, its lifetime and a manual flush.
 */
class SettingsPerformancePage extends MksSettingsPage
{
    protected static ?int $navigationSort = 25;

    public static function getNavigationLabel(): string
    {
        return __('mksine::settings.tabs.performance');
    }

    protected function settingsSchema(): array
    {
        return [
            Toggle::make(PageCache::SETTING_ENABLED)
                ->label(__('mksine::settings.page_cache.enabled'))
                ->helperText(__('mksine::settings.page_cache.enabled_helper')),
            TextInput::make(PageCache::SETTING_TTL_MINUTES)
                ->label(__('mksine::settings.page_cache.ttl'))
                ->helperText(__('mksine::settings.page_cache.ttl_helper'))
                ->numeric()
                ->integer()
                ->minValue(1)
                ->maxValue(10080)
                ->placeholder((string) intdiv((int) config('mksine.page_cache.ttl', 3600), 60)),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clearPageCache')
                ->label(__('mksine::settings.page_cache.clear'))
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function (): void {
                    app(PageCache::class)->flush();

                    Notification::make()
                        ->title(__('mksine::settings.page_cache.cleared'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
