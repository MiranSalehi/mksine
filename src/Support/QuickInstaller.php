<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use RuntimeException;

/**
 * The single-file quick installer (resources/installer/mksine-installer.php) that is
 * published next to each release. Users upload it to their web root; it downloads the
 * newest release from GitHub, checks the checksum and opens /install.
 */
final class QuickInstaller
{
    public const FILE_NAME = 'mksine-installer.php';

    private const BUILD_MARKER = "const MKSI_BUILD = 'dev';";

    public static function sourcePath(): string
    {
        return dirname(__DIR__, 2) . '/resources/installer/' . self::FILE_NAME;
    }

    /**
     * Copy the installer into a release folder with the version stamped in.
     *
     * @throws RuntimeException When the source is missing or the marker was changed.
     */
    public static function write(string $directory, string $version): string
    {
        $source = (string) @file_get_contents(self::sourcePath());

        if (! str_contains($source, self::BUILD_MARKER)) {
            throw new RuntimeException('The quick installer source or its build marker is missing: ' . self::sourcePath());
        }

        $path = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . self::FILE_NAME;
        $stamped = str_replace(self::BUILD_MARKER, 'const MKSI_BUILD = ' . var_export($version, true) . ';', $source);

        if (file_put_contents($path, $stamped) === false) {
            throw new RuntimeException("Could not write {$path}.");
        }

        return $path;
    }
}
