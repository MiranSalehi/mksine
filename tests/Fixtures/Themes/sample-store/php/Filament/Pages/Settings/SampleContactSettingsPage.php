<?php

declare(strict_types=1);

namespace Themes\SampleStore\Filament\Pages\Settings;

use Filament\Pages\Page;

class SampleContactSettingsPage extends Page
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $navigationLabel = 'Contact';

    protected static ?string $title = 'Contact settings';

    protected string $view = 'filament-panels::pages.page';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
