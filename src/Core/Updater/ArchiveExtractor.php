<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater;

use RuntimeException;
use ZipArchive;

/**
 * Safe ZIP extractor with strict path-traversal guard.
 *
 * Rules enforced on every entry before touching disk:
 *   - No absolute paths (leading "/" or Windows "C:\").
 *   - No ".." segments anywhere in the path (including inside double-encoded names).
 *   - No null bytes (%00 injection).
 *   - No symlinks (ZipArchive does not emit them through extractTo anyway, but
 *     post-extract we defensively scan and reject/strip them).
 *   - No absolute-directory-breaks: realpath of every entry after extraction
 *     must start with the destination directory's realpath.
 *
 * This class never extracts into the final target. It always writes to the
 * caller-provided staging directory (which must be empty or not exist).
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

        $zip = new ZipArchive;
        $opened = $zip->open($zipPath, ZipArchive::RDONLY);
        if ($opened !== true) {
            throw UpdateException::validation("Unable to open ZIP (code {$opened}): {$zipPath}");
        }

        $stagingReal = realpath($stagingDir);
        if ($stagingReal === false) {
            $zip->close();
            throw UpdateException::validation("Unable to resolve staging dir: {$stagingDir}");
        }

        // 1) Validate every entry name without extracting anything yet.
        $entryCount = $zip->numFiles;
        for ($i = 0; $i < $entryCount; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false || $name === '') {
                $zip->close();
                throw UpdateException::validation("Invalid entry at index {$i}");
            }

            self::assertSafeEntry($name);
        }

        // 2) Extract.
        if (! $zip->extractTo($stagingDir)) {
            $zip->close();
            throw UpdateException::validation("Failed to extract ZIP to {$stagingDir}");
        }
        $zip->close();

        // 3) Post-extract guard: no files/dirs escape the staging dir via symlinks
        //    or filesystem shenanigans. We walk the tree and re-check realpath.
        self::assertNoEscapes($stagingReal);

        // 4) Detect single-root-folder convention. Providers may ZIP their
        //    payload with or without a wrapping folder.
        return self::resolveContentRoot($stagingReal);
    }

    /**
     * Peek into an entry's content without extracting (for manifest sniffing).
     *
     * Caller passes a predicate that returns true for the first entry matching
     * the file-of-interest (e.g. `plugin.php`, `theme.json`, `composer.json`).
     *
     * @param  callable(string $entryName): bool  $predicate
     */
    public static function readFirstMatching(string $zipPath, callable $predicate): ?string
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::RDONLY) !== true) {
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

    private static function assertSafeEntry(string $name): void
    {
        if (str_contains($name, "\0")) {
            throw UpdateException::validation("Null byte in ZIP entry: {$name}");
        }

        // Normalize to forward slashes for validation (ZIP spec).
        $normalized = str_replace('\\', '/', $name);

        if ($normalized === '' || $normalized[0] === '/') {
            throw UpdateException::validation("Absolute path in ZIP entry: {$name}");
        }

        // Windows-style drive letter.
        if (preg_match('/^[A-Za-z]:/', $normalized) === 1) {
            throw UpdateException::validation("Windows absolute path in ZIP entry: {$name}");
        }

        // Any ".." segment breaks out of destination.
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
                // Immediately reject any symlink in an update archive.
                @unlink($file->getPathname());
                throw UpdateException::validation('Symlink entry not allowed: ' . $file->getPathname());
            }

            $real = realpath($file->getPathname());
            if ($real === false) {
                // Could be a dangling link we failed to unlink above.
                throw UpdateException::validation('Unresolved entry in archive: ' . $file->getPathname());
            }

            if (! str_starts_with($real, $stagingReal . DIRECTORY_SEPARATOR) && $real !== $stagingReal) {
                throw UpdateException::validation('Archive entry escapes staging dir: ' . $file->getPathname());
            }
        }
    }

    private static function resolveContentRoot(string $stagingReal): string
    {
        $entries = [];
        foreach (scandir($stagingReal) ?: [] as $e) {
            if ($e === '.' || $e === '..') {
                continue;
            }
            $entries[] = $e;
        }

        // Single-folder-at-root convention: entire payload is wrapped in ONE dir.
        if (count($entries) === 1 && is_dir($stagingReal . DIRECTORY_SEPARATOR . $entries[0])) {
            return $stagingReal . DIRECTORY_SEPARATOR . $entries[0];
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

    /**
     * Recursively delete a directory. Used by pipeline for cleanup.
     */
    public static function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            if (is_file($path)) {
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
            throw new RuntimeException("Unable to fully delete directory: {$path}");
        }
    }
}
