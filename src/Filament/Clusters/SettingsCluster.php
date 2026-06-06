<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class SettingsCluster extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return __('mksine::common.system');
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
