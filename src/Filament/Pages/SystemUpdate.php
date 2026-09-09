<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Miran\Mksine\Core\Updater\SuperAdminGate;
use Miran\Mksine\Filament\Support\AdminNavigationGroup;
use Miran\Mksine\Support\ComposerBinary;
use Miran\Mksine\Support\PackageVersion;

/**
 * Super-Admin page explaining how to update miran/mksine via Composer.
 * Core ZIP replacement is not supported.
 */
class SystemUpdate extends Page
{
    use HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected string $view = 'mksine::filament.pages.system-update';

    protected static ?string $slug = 'system-update';

    protected static ?int $navigationSort = 9999;

    public static function getNavigationLabel(): string
    {
        return __('mksine::updater.core_navigation_label');
    }

    public static function getNavigationGroup(): string|\UnitEnum|null
    {
        return AdminNavigationGroup::System;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return SuperAdminGate::check() && (bool) config('mksine.updater.enabled', true);
    }

    public static function canAccess(): bool
    {
        return SuperAdminGate::check() && (bool) config('mksine.updater.enabled', true);
    }

    public function getTitle(): string
    {
        return __('mksine::updater.core_title');
    }

    public function getSubheading(): ?string
    {
        return __('mksine::updater.core_subheading', [
            'version' => PackageVersion::current(),
        ]);
    }

    public function getCurrentVersion(): string
    {
        return PackageVersion::current();
    }

    public function composerIsAvailable(): bool
    {
        return ComposerBinary::isAvailable(base_path());
    }

    public function consoleTerminalUrl(): ?string
    {
        if (! ConsoleTerminal::canAccess()) {
            return null;
        }

        try {
            return ConsoleTerminal::getUrl();
        } catch (\Throwable) {
            return null;
        }
    }
}
