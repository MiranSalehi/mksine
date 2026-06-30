<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Support;

use Filament\Panel;
use Miran\Mksine\Filament\Pages\MksineDashboard;

/**
 * Guards against stale Filament panel component caches on HTTP requests.
 *
 * When {@see Panel::hasCachedComponents()} is true, {@see Panel::discoverPages()} is skipped.
 * A cache file created before MKSine was added (or without {@see MksineDashboard}) leaves the
 * dashboard route undefined while Filament still resolves URLs to it — causing 500s on /admin.
 */
final class FilamentPanelComponentCache
{
    public static function ensureDashboardPageRegistered(Panel $panel): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        if (! $panel->hasCachedComponents()) {
            return;
        }

        if (in_array(MksineDashboard::class, $panel->getPages(), true)) {
            return;
        }

        $panel->clearCachedComponents();
    }

    /**
     * @param  array{pages?: list<class-string>}  $cache
     */
    public static function cacheIsMissingDashboard(array $cache): bool
    {
        return ! in_array(MksineDashboard::class, $cache['pages'] ?? [], true);
    }
}
