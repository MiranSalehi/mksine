<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Support;

use Filament\Pages\Dashboard as FilamentDashboard;
use Filament\Panel;
use Miran\Mksine\Filament\Pages\MksineDashboard;
use ReflectionProperty;

/**
 * Ensures {@see MksineDashboard} is the sole Filament dashboard page on MKSine panels.
 *
 * Host apps created with `filament:install --panels` register {@see FilamentDashboard} at `/`.
 * MKSine discovers {@see MksineDashboard} on the same path. Filament then registers only one
 * route name; navigation still points at `filament.admin.pages.mksine-dashboard` and 500s.
 */
final class FilamentPanelDashboard
{
    public static function replaceHostDefaultDashboard(Panel $panel): void
    {
        if (! $panel->hasPlugin('mksine')) {
            return;
        }

        $pages = self::pages($panel);

        $filtered = array_values(array_filter(
            $pages,
            static fn (string $page): bool => $page !== FilamentDashboard::class,
        ));

        if (! in_array(MksineDashboard::class, $filtered, true)) {
            $filtered[] = MksineDashboard::class;
        }

        if ($filtered === $pages) {
            return;
        }

        self::setPages($panel, $filtered);
    }

    /**
     * @return list<class-string>
     */
    private static function pages(Panel $panel): array
    {
        $property = new ReflectionProperty($panel, 'pages');
        $property->setAccessible(true);

        /** @var list<class-string> $pages */
        $pages = $property->getValue($panel);

        return $pages;
    }

    /**
     * @param  list<class-string>  $pages
     */
    private static function setPages(Panel $panel, array $pages): void
    {
        $property = new ReflectionProperty($panel, 'pages');
        $property->setAccessible(true);
        $property->setValue($panel, $pages);
    }
}
