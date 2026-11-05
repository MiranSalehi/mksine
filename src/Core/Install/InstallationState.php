<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Miran\Mksine\Support\PackageVersion;
use Throwable;

/**
 * Answers "is MKSine installed on this site?".
 *
 * A finished installation writes a lock file. Sites installed before the lock file existed are
 * still treated as installed when the core tables are present, unless an installation is in progress.
 */
class InstallationState
{
    public const LOCK_FILE = 'mksine/installed.json';

    public const PROGRESS_FILE = 'mksine/install-state.json';

    /**
     * Tables whose presence marks an installation that predates the lock file.
     *
     * @var list<string>
     */
    public const LEGACY_MARKER_TABLES = ['mks_plugins', 'mks_hooks'];

    private ?bool $installed = null;

    public function __construct(private readonly Filesystem $files) {}

    public function isInstalled(): bool
    {
        if ($this->installed !== null) {
            return $this->installed;
        }

        if ($this->hasLockFile()) {
            return $this->installed = true;
        }

        if ($this->isInstallationInProgress() || ! $this->hasLegacyInstallation()) {
            return $this->installed = false;
        }

        $this->persistLegacyLock();

        return $this->installed = true;
    }

    public function hasLockFile(): bool
    {
        return $this->files->exists($this->lockFilePath());
    }

    public function isInstallationInProgress(): bool
    {
        return $this->files->exists($this->progressFilePath());
    }

    public function lockFilePath(): string
    {
        return storage_path(self::LOCK_FILE);
    }

    public function progressFilePath(): string
    {
        return storage_path(self::PROGRESS_FILE);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function markInstalled(array $metadata = []): void
    {
        $path = $this->lockFilePath();

        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, (string) json_encode([
            ...$metadata,
            'version' => PackageVersion::current(),
            'installed_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->installed = true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function metadata(): ?array
    {
        if (! $this->hasLockFile()) {
            return null;
        }

        $decoded = json_decode((string) $this->files->get($this->lockFilePath()), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Drop the cached answer so the next check reads the disk and database again.
     */
    public function refresh(): void
    {
        $this->installed = null;
    }

    /**
     * Write the lock file for a site installed before it existed, so later requests
     * skip the database check. Skipped in tests so they never touch the real storage.
     */
    private function persistLegacyLock(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        try {
            $this->markInstalled(['legacy' => true]);
        } catch (Throwable) {
            // A read-only storage directory only costs the database check on each request.
        }
    }

    private function hasLegacyInstallation(): bool
    {
        try {
            foreach (self::LEGACY_MARKER_TABLES as $table) {
                if (! Schema::hasTable($table)) {
                    return false;
                }
            }

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
