<?php

namespace Miran\Mksine\Filament\Pages;

use Filament\Pages\Page;
use Miran\Mksine\Filament\Pages\Settings\SettingsGeneralPage;

/**
 * @deprecated Use {@see SettingsCluster} pages. Kept for backward-compatible URLs.
 */
class Settings extends Page
{
    /**
     * Slug conflicts with {@see SettingsCluster}; undiscovered so the cluster owns /admin/settings.
     */
    protected static bool $isDiscovered = false;

    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        $this->redirect(SettingsGeneralPage::getUrl());
    }
}

