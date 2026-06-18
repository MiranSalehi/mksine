<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages\Settings;

use Filament\Schemas\Components\Tabs;
use Miran\Mksine\Core\Hooks\SettingsTabManager;

/**
 * Legacy plugin settings still registered via {@see SettingsTabManager}. Prefer {@see MksSettingsPage} subclasses (see ecom, mks-notification, mks-booking).
 */
class SettingsExtensionsPage extends MksSettingsPage
{
    protected static ?int $navigationSort = 200;

    public static function getNavigationLabel(): string
    {
        return __('mksine::settings.tabs.extensions');
    }

    public static function shouldRegisterNavigation(): bool
    {
        if (! parent::shouldRegisterNavigation()) {
            return false;
        }

        if (! app()->bound(SettingsTabManager::class)) {
            return false;
        }

        return app(SettingsTabManager::class)->hasTabs();
    }

    protected function settingsSchema(): array
    {
        return [
            Tabs::make()
                ->vertical()
                ->tabs(app(SettingsTabManager::class)->getTabs())
                ->persistTabInQueryString('settings_extension_tab')
                ->columnSpanFull(),
        ];
    }
}
