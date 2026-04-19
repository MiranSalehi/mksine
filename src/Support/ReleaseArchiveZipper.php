<?php

namespace Miran\Mksine\Support;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use ZipArchive;

class ReleaseArchiveZipper
{
    /**
     * Create a zip of $projectRoot applying {@see ReleaseArchiveBuildRoots::shouldIncludeInZip}.
     *
     * @throws \RuntimeException
     */
    public static function createArchive(string $projectRoot, string $zipAbsolutePath): void
    {
        $projectRootReal = realpath($projectRoot);
        if ($projectRootReal === false) {
            throw new \RuntimeException("Project root not found: {$projectRoot}");
        }

        $projectRootReal = rtrim($projectRootReal, DIRECTORY_SEPARATOR);
        $zipDir = dirname($zipAbsolutePath);
        if (! is_dir($zipDir)) {
            if (! mkdir($zipDir, 0755, true) && ! is_dir($zipDir)) {
                throw new \RuntimeException("Could not create directory: {$zipDir}");
            }
        }

        if (file_exists($zipAbsolutePath)) {
            unlink($zipAbsolutePath);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipAbsolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Could not open zip for writing: {$zipAbsolutePath}");
        }

        $excludeRelative = self::zipPathRelativeToProject($projectRootReal, $zipAbsolutePath);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $projectRootReal,
                RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $normalizedRoot = str_replace('\\', '/', $projectRootReal);

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            // Logical path keeps vendor/{package} prefix for Composer path-repo symlinks
            // (e.g. vendor/miran/mksine -> packages/mksine). realpath() would rewrite to
            // packages/mksine/... and break autoload paths on deploy.
            $pathname = $file->getPathname();
            $normalizedFile = str_replace('\\', '/', $pathname);
            $rootPrefix = $normalizedRoot.'/';
            if (! str_starts_with($normalizedFile, $rootPrefix)) {
                continue;
            }

            $relative = substr($normalizedFile, strlen($rootPrefix));

            if (! ReleaseArchiveBuildRoots::shouldIncludeInZip($relative)) {
                continue;
            }

            if ($excludeRelative !== null && $relative === $excludeRelative) {
                continue;
            }

            if (! $zip->addFile($pathname, $relative)) {
                $zip->close();
                unlink($zipAbsolutePath);
                throw new \RuntimeException("Failed adding file to zip: {$relative}");
            }
        }

        $zip->close();
    }

    /**
     * Resolve a user-provided output path to an absolute path.
     */
    public static function resolveOutputPath(string $projectRoot, ?string $outputOption): string
    {
        $projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);

        if ($outputOption === null || $outputOption === '') {
            return $projectRoot.DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'mksine-release-'.date('Y-m-d-His').'.zip';
        }

        if (self::isAbsolutePath($outputOption)) {
            return $outputOption;
        }

        return $projectRoot.DIRECTORY_SEPARATOR.ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $outputOption), DIRECTORY_SEPARATOR);
    }

    /**
     * If the zip is written inside the project tree, return its path relative to the project (forward slashes) so it is not packed into itself.
     */
    private static function zipPathRelativeToProject(string $projectRootReal, string $zipAbsolutePath): ?string
    {
        $root = str_replace('\\', '/', rtrim($projectRootReal, '/'));
        $zip = str_replace('\\', '/', $zipAbsolutePath);
        $prefix = $root.'/';

        if (! str_starts_with($zip, $prefix)) {
            return null;
        }

        return substr($zip, strlen($prefix));
    }

    private static function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if ($path[0] === '/' || $path[0] === '\\') {
            return true;
        }

        return strlen($path) > 2 && ctype_alpha($path[0]) && $path[1] === ':' && ($path[2] === '\\' || $path[2] === '/');
    }
}
