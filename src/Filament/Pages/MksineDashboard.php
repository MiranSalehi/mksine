<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Miran\Mksine\Core\Hooks\PageHookManager;

/**
 * Hookable admin dashboard. Plugins register widgets and header actions via:
 *
 * - {@see \Miran\Mksine\Core\Hooks\Hooks::extendDashboardWidgets()}
 * - {@see \Miran\Mksine\Core\Hooks\Hooks::extendPageHeaderActions()} with {@see self::HOOK_NAME}
 */
class MksineDashboard extends BaseDashboard
{
    public const string HOOK_NAME = 'dashboard';

    /**
     * @return array<class-string<\Filament\Widgets\Widget> | \Filament\Widgets\WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return app(PageHookManager::class)->applyWidgets(
            self::HOOK_NAME,
            parent::getWidgets(),
        );
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return app(PageHookManager::class)->applyHeaderActions(
            self::HOOK_NAME,
            parent::getHeaderActions(),
        );
    }
}
