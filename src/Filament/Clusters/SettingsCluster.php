<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use Miran\Mksine\Filament\Pages\Settings\SettingsMenuPage;

class SettingsCluster extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 80;

    public static function getNavigationGroup(): ?string
    {
        // Top-level like WordPress Settings — not nested under System.
        return null;
    }

    /**
     * Open only when at least one settings page or resource inside the cluster is allowed.
     * Super admins always qualify through {@see SettingsMenuPage}.
     */
    public static function canAccess(): bool
    {
        return static::canAccessClusteredComponents();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess() && static::$shouldRegisterNavigation;
    }

    public static function getNavigationLabel(): string
    {
        return __('mksine::settings.navigation_label');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('mksine::settings.title');
    }
}
