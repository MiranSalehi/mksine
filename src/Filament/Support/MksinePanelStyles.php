<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Support;

use Illuminate\Support\Facades\Route;

/**
 * Injects MKSine admin CSS after the Filament panel theme so Tailwind utilities are not overridden.
 *
 * The stylesheet is served from the package tree (`resources/dist/mksine.css`) with a
 * filemtime query string. Composer updates pick up new CSS without `filament:assets`.
 */
final class MksinePanelStyles
{
    public static function cssPath(): ?string
    {
        $path = dirname(__DIR__, 3).'/resources/dist/mksine.css';

        return is_file($path) ? $path : null;
    }

    public static function stylesheetHref(): string
    {
        $path = self::cssPath();

        if ($path === null || ! Route::has('mksine.admin-styles')) {
            return '';
        }

        $version = once(static fn (): string => (string) filemtime($path).'.'.filesize($path));

        return route('mksine.admin-styles', ['v' => $version]);
    }

    public static function renderAfterTheme(): string
    {
        $href = self::stylesheetHref();

        if ($href === '') {
            return '';
        }

        return '<link href="'.e($href).'" rel="stylesheet" data-mksine-styles data-navigate-track />';
    }
}
