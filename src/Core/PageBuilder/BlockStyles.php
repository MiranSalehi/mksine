<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\PageBuilder;

use Throwable;

/**
 * Styles for the shared block renderer. Loaded with the blocks, not from the active theme.
 */
final class BlockStyles
{
    public const PUBLISHED_RELATIVE = 'css/miran/mksine/mksine-blocks.css';

    public static function cssPath(): ?string
    {
        $path = dirname(__DIR__, 3).'/resources/dist/blocks.css';

        return is_file($path) ? $path : null;
    }

    public static function renderTag(): string
    {
        $source = self::cssPath();
        if ($source === null) {
            return '';
        }

        $href = self::publishedHref($source);
        if ($href === null) {
            return '';
        }

        return '<link rel="stylesheet" href="'.e($href).'" data-mksine-blocks>';
    }

    private static function publishedHref(string $source): ?string
    {
        $destination = public_path(self::PUBLISHED_RELATIVE);

        try {
            $directory = dirname($destination);
            if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                return null;
            }

            if (! is_file($destination) || filesize($destination) !== filesize($source) || filemtime($destination) < filemtime($source)) {
                if (! copy($source, $destination)) {
                    return null;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return '/'.self::PUBLISHED_RELATIVE.'?v='.filemtime($source);
    }
}
