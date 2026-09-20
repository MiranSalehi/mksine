<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Support;

use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\Route;
use LogicException;
use Throwable;

/**
 * Injects MKSine admin CSS after the Filament panel theme so Tailwind utilities are not overridden.
 *
 * Production panels already serve `/css/miran/mksine/…` (Filament assets). A new
 * `/mksine/admin-styles.css` route is a fallback — many hosts only proxy `/admin` and `/css`.
 * The published file is copied from the package when it is missing or stale, so
 * `filament:assets` is not required after a Composer update.
 */
final class MksinePanelStyles
{
    public const PUBLISHED_RELATIVE = 'css/miran/mksine/mksine-styles.css';

    public static function cssPath(): ?string
    {
        $path = dirname(__DIR__, 3).'/resources/dist/mksine.css';

        return is_file($path) ? $path : null;
    }

    public static function publishedPath(): string
    {
        return public_path(self::PUBLISHED_RELATIVE);
    }

    public static function stylesheetHref(): string
    {
        $source = self::cssPath();
        $version = self::stylesheetVersion($source);

        $published = self::syncPublishedStyles($source);
        if ($published !== null) {
            return $published.'?v='.$version;
        }

        if (Route::has('mksine.admin-styles')) {
            return route('mksine.admin-styles', ['v' => $version], absolute: false);
        }

        try {
            $href = FilamentAsset::getStyleHref('mksine-styles', 'miran/mksine');
        } catch (LogicException) {
            return '';
        }

        if ($href === '') {
            return '';
        }

        return $href.(str_contains($href, '?') ? '&' : '?').'v='.$version;
    }

    public static function renderAfterTheme(): string
    {
        $href = self::stylesheetHref();

        if ($href === '') {
            return '';
        }

        return '<link href="'.e($href).'" rel="stylesheet" data-mksine-styles data-navigate-track />';
    }

    public static function stylesheetVersion(?string $source = null): string
    {
        $source ??= self::cssPath();

        if ($source === null) {
            return (string) config('mksine.version', 'dev');
        }

        return once(static fn (): string => (string) filemtime($source).'.'.filesize($source));
    }

    public static function syncPublishedStyles(?string $source = null): ?string
    {
        $source ??= self::cssPath();
        $relative = '/'.self::PUBLISHED_RELATIVE;

        if ($source === null) {
            return is_file(self::publishedPath()) ? $relative : null;
        }

        return once(function () use ($source, $relative): ?string {
            $dest = self::publishedPath();

            if (is_file($dest) && filesize($dest) === filesize($source) && filemtime($dest) >= filemtime($source)) {
                return $relative;
            }

            try {
                $directory = dirname($dest);
                if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                    return null;
                }

                if (! is_writable($directory)) {
                    return null;
                }

                if (! copy($source, $dest)) {
                    return null;
                }
            } catch (Throwable) {
                return null;
            }

            return $relative;
        });
    }
}
