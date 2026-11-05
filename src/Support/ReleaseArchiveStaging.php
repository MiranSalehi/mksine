<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Copies the project without vendor/ into a staging directory, so Composer can install
 * production-only dependencies there before the zip is made.
 */
final class ReleaseArchiveStaging
{
    /**
     * @return int Number of files copied.
     */
    public static function copyProject(string $projectRoot, string $stagingRoot, bool $includeExtensions): int
    {
        $projectRoot = rtrim(str_replace('\\', '/', (string) realpath($projectRoot)), '/');
        File::ensureDirectoryExists($stagingRoot);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($projectRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        $copied = 0;

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }

            $relative = substr(str_replace('\\', '/', $file->getPathname()), strlen($projectRoot) + 1);

            if (! self::shouldStage($relative, $includeExtensions)) {
                continue;
            }

            $target = rtrim($stagingRoot, '/\\') . '/' . $relative;
            File::ensureDirectoryExists(dirname($target));
            File::copy($file->getPathname(), $target);
            $copied++;
        }

        return $copied;
    }

    /**
     * Everything the zip would take except vendor/, plus the whole path-repository package
     * source that `composer install` links into vendor/miran/mksine.
     */
    public static function shouldStage(string $relativePath, bool $includeExtensions): bool
    {
        if ($relativePath === 'vendor' || str_starts_with($relativePath, 'vendor/')) {
            return false;
        }

        if (str_starts_with($relativePath, 'packages/mksine/')) {
            return ! str_contains($relativePath, '/node_modules/')
                && ! str_starts_with($relativePath, 'packages/mksine/vendor/')
                && ! str_starts_with($relativePath, 'packages/mksine/.git/');
        }

        return ReleaseArchiveBuildRoots::shouldIncludeInZip($relativePath, $includeExtensions);
    }
}
