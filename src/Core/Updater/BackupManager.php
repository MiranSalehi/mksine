<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater;

/**
 * Creates and prunes on-disk backups of an update target directory.
 *
 * Layout — backups live alongside the target, e.g. for plugin "acme":
 *   plugins/.mks-backups/acme-20260421-143055-v1.4.2/
 *
 * Living on the SAME filesystem as the target is essential so the swap in
 * AtomicReplacer can use rename() (atomic on POSIX when same mount).
 *
 * Retention: config('mksine.updater.keep_backups') defaults to 3. The OLDEST
 * backups beyond that are removed after a successful update commits.
 */
final class BackupManager
{
    public function __construct(
        private readonly UpdateTarget $target,
        private readonly string $identifier,
    ) {}

    /**
     * Directory where all backups for THIS target/identifier are stored.
     * Must live inside the target's parent so rename() is atomic.
     */
    public function backupsRoot(string $targetPath): string
    {
        $parent = rtrim(dirname($targetPath), DIRECTORY_SEPARATOR);

        return $parent . DIRECTORY_SEPARATOR . '.mks-backups';
    }

    /**
     * Compute a new backup directory path for a single run.
     */
    public function newBackupPath(string $targetPath, ?string $fromVersion): string
    {
        $ts = date('Ymd-His');
        $version = $fromVersion !== null && $fromVersion !== ''
            ? '-v' . preg_replace('/[^A-Za-z0-9\.\-_]/', '_', $fromVersion)
            : '';

        $safeId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $this->identifier) ?: 'unknown';

        return $this->backupsRoot($targetPath) . DIRECTORY_SEPARATOR . $safeId . '-' . $ts . $version;
    }

    /**
     * Ensure the backups root exists.
     */
    public function ensureRoot(string $targetPath): string
    {
        $root = $this->backupsRoot($targetPath);
        if (! is_dir($root) && ! @mkdir($root, 0755, true) && ! is_dir($root)) {
            throw UpdateException::replace("Unable to create backups root: {$root}");
        }

        return $root;
    }

    /**
     * Prune old backups for THIS identifier beyond the keep_backups limit.
     *
     * Returns the paths that were removed (for logging).
     *
     * @return list<string>
     */
    public function prune(string $targetPath, int $keep): array
    {
        if ($keep < 1) {
            $keep = 1;
        }

        $root = $this->backupsRoot($targetPath);
        if (! is_dir($root)) {
            return [];
        }

        $safeId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $this->identifier) ?: 'unknown';
        $prefix = $safeId . '-';

        $entries = [];
        foreach (scandir($root) ?: [] as $e) {
            if ($e === '.' || $e === '..') {
                continue;
            }
            if (! str_starts_with($e, $prefix)) {
                continue;
            }
            $full = $root . DIRECTORY_SEPARATOR . $e;
            if (! is_dir($full)) {
                continue;
            }
            $entries[$full] = filemtime($full) ?: 0;
        }

        if (count($entries) <= $keep) {
            return [];
        }

        // Oldest first.
        asort($entries);
        $toRemove = array_slice(array_keys($entries), 0, count($entries) - $keep);

        $removed = [];
        foreach ($toRemove as $dir) {
            try {
                ArchiveExtractor::deleteDirectory($dir);
                $removed[] = $dir;
            } catch (\Throwable) {
                // Non-fatal. Keep going.
            }
        }

        return $removed;
    }

    /**
     * Find the most recent backup for this identifier (for rollback).
     */
    public function latestBackup(string $targetPath): ?string
    {
        $root = $this->backupsRoot($targetPath);
        if (! is_dir($root)) {
            return null;
        }

        $safeId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $this->identifier) ?: 'unknown';
        $prefix = $safeId . '-';

        $latest = null;
        $latestMtime = -1;
        foreach (scandir($root) ?: [] as $e) {
            if ($e === '.' || $e === '..') {
                continue;
            }
            if (! str_starts_with($e, $prefix)) {
                continue;
            }
            $full = $root . DIRECTORY_SEPARATOR . $e;
            if (! is_dir($full)) {
                continue;
            }
            $mtime = filemtime($full) ?: 0;
            if ($mtime > $latestMtime) {
                $latestMtime = $mtime;
                $latest = $full;
            }
        }

        return $latest;
    }
}
