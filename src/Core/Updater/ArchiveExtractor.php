<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater;

/**
 * Safe ZIP extractor with strict path-traversal guard.
 *
 * Entries are written one-by-one (never ZipArchive::extractTo) so a symlink
 * cannot redirect a later write outside the staging directory.
 */
final class ArchiveExtractor
{
    /**
     * Extract $zipPath into $stagingDir.
     *
     * Returns the absolute path of the single top-level directory inside the
     * archive, or the staging dir itself if files are at the ZIP root.
     *
     * @throws UpdateException
     */
    public static function extract(string $zipPath, string $stagingDir): string
    {
        if (! is_file($zipPath) || ! is_readable($zipPath)) {
            throw UpdateException::validation("Archive not readable: {$zipPath}");
        }

        if (is_dir($stagingDir) && ! self::isEmptyDir($stagingDir)) {
            throw UpdateException::validation("Staging directory is not empty: {$stagingDir}");
        }

        if (! is_dir($stagingDir) && ! @mkdir($stagingDir, 0755, true) && ! is_dir($stagingDir)) {
            throw UpdateException::validation("Unable to create staging directory: {$stagingDir}");
        }

        $zip = new \ZipArchive;
        $flags = defined('ZipArchive::RDONLY') ? \ZipArchive::RDONLY : 0;
        $opened = $zip->open($zipPath, $flags);
        if ($opened !== true) {
            throw UpdateException::validation("Unable to open ZIP (code {$opened}): {$zipPath}");
        }

        $stagingReal = realpath($stagingDir);
        if ($stagingReal === false) {
            $zip->close();
            throw UpdateException::validation("Unable to resolve staging dir: {$stagingDir}");
        }

        try {
            self::assertArchiveLimits($zip);
            self::extractEntries($zip, $stagingReal);
        } finally {
            $zip->close();
        }

        self::assertNoEscapes($stagingReal);

        return self::resolveContentRoot($stagingReal);
    }

    /**
     * Peek into an entry's content without extracting (for manifest sniffing).
     *
     * @param  callable(string $entryName): bool  $predicate
     */
    public static function readFirstMatching(string $zipPath, callable $predicate): ?string
    {
        $zip = new \ZipArchive;
        $flags = defined('ZipArchive::RDONLY') ? \ZipArchive::RDONLY : 0;
        if ($zip->open($zipPath, $flags) !== true) {
            throw UpdateException::validation("Unable to open ZIP: {$zipPath}");
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) {
                continue;
            }
            if ($predicate($name)) {
                $content = $zip->getFromIndex($i);
                $zip->close();

                return $content === false ? null : $content;
            }
        }

        $zip->close();

        return null;
    }

    /**
     * Recursively delete a directory. Used by pipeline for cleanup.
     */
    public static function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            if (is_file($path) || is_link($path)) {
                @unlink($path);
            }

            return;
        }

        $it = new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $walker = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($walker as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isLink() || $file->isFile()) {
                @unlink($file->getPathname());
            } elseif ($file->isDir()) {
                @rmdir($file->getPathname());
            }
        }

        @rmdir($path);

        if (is_dir($path)) {
            throw new \RuntimeException("Unable to fully delete directory: {$path}");
        }
    }

    private static function assertArchiveLimits(\ZipArchive $zip): void
    {
        $maxEntries = max(1, (int) config('mksine.updater.max_zip_entries', 10000));
        $maxUncompressedMb = max(1, (int) config('mksine.updater.max_uncompressed_mb', 1024));
        $maxUncompressed = $maxUncompressedMb * 1024 * 1024;

        $entryCount = $zip->numFiles;
        if ($entryCount > $maxEntries) {
            throw UpdateException::validation("ZIP has {$entryCount} entries; maximum is {$maxEntries}.");
        }

        $uncompressed = 0;
        for ($i = 0; $i < $entryCount; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false || $name === '') {
                throw UpdateException::validation("Invalid entry at index {$i}");
            }

            self::assertSafeEntry($name);

            if (self::isSymlinkEntry($zip, $i)) {
                throw UpdateException::validation("Symlink entry not allowed: {$name}");
            }

            $stat = $zip->statIndex($i);
            $size = is_array($stat) ? (int) ($stat['size'] ?? 0) : 0;
            $uncompressed += $size;
            if ($uncompressed > $maxUncompressed) {
                throw UpdateException::validation("ZIP uncompressed size exceeds {$maxUncompressedMb} MB.");
            }
        }
    }

    private static function extractEntries(\ZipArchive $zip, string $stagingReal): void
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) {
                throw UpdateException::validation("Invalid entry at index {$i}");
            }

            $normalized = str_replace('\\', '/', $name);
            $relative = rtrim($normalized, '/');
            if ($relative === '') {
                continue;
            }

            $destination = $stagingReal.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
            self::assertDestinationInsideStaging($stagingReal, $destination);

            $isDirectory = str_ends_with($normalized, '/');
            if ($isDirectory) {
                if (! is_dir($destination) && ! @mkdir($destination, 0755, true) && ! is_dir($destination)) {
                    throw UpdateException::validation("Unable to create directory from ZIP: {$name}");
                }

                continue;
            }

            $parent = dirname($destination);
            if (! is_dir($parent) && ! @mkdir($parent, 0755, true) && ! is_dir($parent)) {
                throw UpdateException::validation("Unable to create parent directory for ZIP entry: {$name}");
            }

            $stream = $zip->getStream($name);
            if ($stream === false) {
                throw UpdateException::validation("Unable to read ZIP entry: {$name}");
            }

            $out = @fopen($destination, 'wb');
            if ($out === false) {
                fclose($stream);
                throw UpdateException::validation("Unable to write ZIP entry: {$name}");
            }

            try {
                stream_copy_to_stream($stream, $out);
            } finally {
                fclose($out);
                fclose($stream);
            }
        }
    }

    private static function assertDestinationInsideStaging(string $stagingReal, string $destination): void
    {
        $parent = dirname($destination);
        $resolvedParent = realpath($parent);
        if ($resolvedParent === false) {
            $resolvedParent = $parent;
        }

        $prefix = $stagingReal.DIRECTORY_SEPARATOR;
        if ($resolvedParent !== $stagingReal && ! str_starts_with($resolvedParent, $prefix)) {
            throw UpdateException::validation("Archive entry escapes staging dir: {$destination}");
        }

        $realDest = realpath($destination);
        if ($realDest !== false && $realDest !== $stagingReal && ! str_starts_with($realDest, $prefix)) {
            throw UpdateException::validation("Archive entry escapes staging dir: {$destination}");
        }
    }

    private static function isSymlinkEntry(\ZipArchive $zip, int $index): bool
    {
        $opsys = 0;
        $attr = 0;
        if (! $zip->getExternalAttributesIndex($index, $opsys, $attr)) {
            return false;
        }

        if ($opsys === \ZipArchive::OPSYS_UNIX) {
            $mode = ($attr >> 16) & 0xFFFF;

            // S_IFLNK = 0120000
            return ($mode & 0xF000) === 0xA000;
        }

        return false;
    }

    private static function assertSafeEntry(string $name): void
    {
        if (str_contains($name, "\0")) {
            throw UpdateException::validation("Null byte in ZIP entry: {$name}");
        }

        $normalized = str_replace('\\', '/', $name);

        if ($normalized === '' || $normalized[0] === '/') {
            throw UpdateException::validation("Absolute path in ZIP entry: {$name}");
        }

        if (preg_match('/^[A-Za-z]:/', $normalized) === 1) {
            throw UpdateException::validation("Windows absolute path in ZIP entry: {$name}");
        }

        foreach (explode('/', $normalized) as $segment) {
            if ($segment === '..') {
                throw UpdateException::validation("Path traversal in ZIP entry: {$name}");
            }
        }
    }

    private static function assertNoEscapes(string $stagingReal): void
    {
        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($stagingReal, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($rii as $file) {
            /** @var \SplFileInfo $file */
            if ($file->isLink()) {
                @unlink($file->getPathname());
                throw UpdateException::validation('Symlink entry not allowed: '.$file->getPathname());
            }

            $real = realpath($file->getPathname());
            if ($real === false) {
                throw UpdateException::validation('Unresolved entry in archive: '.$file->getPathname());
            }

            if (! str_starts_with($real, $stagingReal.DIRECTORY_SEPARATOR) && $real !== $stagingReal) {
                throw UpdateException::validation('Archive entry escapes staging dir: '.$file->getPathname());
            }
        }
    }

    private static function resolveContentRoot(string $stagingReal): string
    {
        $entries = [];
        foreach (scandir($stagingReal) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $entries[] = $entry;
        }

        if (count($entries) === 1 && is_dir($stagingReal.DIRECTORY_SEPARATOR.$entries[0])) {
            return $stagingReal.DIRECTORY_SEPARATOR.$entries[0];
        }

        return $stagingReal;
    }

    private static function isEmptyDir(string $dir): bool
    {
        $entries = @scandir($dir);
        if ($entries === false) {
            return false;
        }

        return count(array_diff($entries, ['.', '..'])) === 0;
    }
}
