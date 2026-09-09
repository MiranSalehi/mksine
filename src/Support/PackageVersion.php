<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

/**
 * Reads the shipped package version from packages/mksine (or vendor/miran/mksine),
 * not from a published overlay of config/mksine.php which can go stale.
 */
final class PackageVersion
{
    public static function packageRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    public static function current(): string
    {
        $file = self::packageRoot().DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'mksine.php';
        $fromFile = self::readVersionFromConfigFile($file);
        if ($fromFile !== null) {
            return $fromFile;
        }

        $configured = config('mksine.version');

        return is_string($configured) && $configured !== '' ? $configured : '0.0.0';
    }

    /**
     * Path to package migrations, relative to the Laravel application base path.
     */
    public static function migrationsPathRelativeToBase(): string
    {
        $absolute = self::packageRoot().DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'migrations';
        $base = base_path();
        $baseReal = realpath($base) ?: $base;
        $absReal = realpath($absolute) ?: $absolute;

        if (str_starts_with($absReal, rtrim($baseReal, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)) {
            return ltrim(substr($absReal, strlen($baseReal)), DIRECTORY_SEPARATOR);
        }

        return 'vendor/miran/mksine/database/migrations';
    }

    public static function readVersionFromConfigFile(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $contents = (string) file_get_contents($path);
        if (preg_match("/'version'\s*=>\s*'([^']+)'/", $contents, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
