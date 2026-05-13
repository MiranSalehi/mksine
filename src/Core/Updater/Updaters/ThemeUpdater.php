<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater\Updaters;

use Illuminate\Support\Facades\Artisan;
use Miran\Mksine\Core\Theme\ThemeManager as ThemeManagerService;
use Miran\Mksine\Core\Updater\ArchiveExtractor;
use Miran\Mksine\Core\Updater\AtomicReplacer;
use Miran\Mksine\Core\Updater\BackupManager;
use Miran\Mksine\Core\Updater\UpdateException;
use Miran\Mksine\Core\Updater\UpdateLog;
use Miran\Mksine\Core\Updater\UpdateResult;
use Miran\Mksine\Core\Updater\UpdateRunner;
use Miran\Mksine\Core\Updater\UpdateTarget;

/**
 * Updates an installed project theme from a ZIP upload.
 *
 * Only project themes (living under resources/views/themes/{id}) are updatable.
 * Package themes ship via composer and must be updated through a deploy.
 *
 * Pipeline:
 *   1. Lock.
 *   2. Extract to staging dir next to target (same filesystem).
 *   3. Validate theme.json; ZIP identifier must equal target id; version must be higher.
 *   4. Require dist/ to be present (pre-built assets; server has no npm).
 *   5. Atomic swap.
 *   6. Clear theme cache; republish assets + translations.
 *
 * Theme updates never run migrations, so there is no DB-dirty risk.
 * The active theme remains active — views are picked up on the next render.
 */
final class ThemeUpdater
{
    public function __construct(
        private readonly UpdateRunner $runner,
        private readonly ThemeManagerService $themeManager,
    ) {}

    public function update(string $themeIdentifier, string $zipPath, bool $force = false): UpdateResult
    {
        return $this->runner->run(
            UpdateTarget::Theme,
            $themeIdentifier,
            function (UpdateLog $log, array &$steps, array &$warnings) use ($themeIdentifier, $zipPath, $force): array {
                return $this->execute($themeIdentifier, $zipPath, $force, $log, $steps, $warnings);
            }
        );
    }

    /**
     * @param  array<int,string>  $steps
     * @param  array<int,string>  $warnings
     * @return array{from: ?string, to: string, backup: ?string}
     */
    private function execute(
        string $themeIdentifier,
        string $zipPath,
        bool $force,
        UpdateLog $log,
        array &$steps,
        array &$warnings,
    ): array {
        $current = $this->themeManager->get($themeIdentifier);
        if ($current === null) {
            throw UpdateException::validation("Theme '{$themeIdentifier}' is not discovered.");
        }

        if ($current->isPackageTheme()) {
            throw UpdateException::validation(
                "Theme '{$themeIdentifier}' is a packaged (composer) theme. Package themes cannot be updated via ZIP — update them via composer."
            );
        }

        $themesDir = realpath(resource_path('views/themes'));
        $currentReal = realpath($current->path);
        if ($themesDir === false || $currentReal === false || ! str_starts_with($currentReal, $themesDir . DIRECTORY_SEPARATOR)) {
            throw UpdateException::validation("Theme '{$themeIdentifier}' path is not inside the project themes directory.");
        }

        $this->assertMaxZipSize($zipPath);

        $fromVersion = $current->version;
        $log->step('validate-zip', "zip={$zipPath}");
        $steps[] = 'validate-zip';

        $stagingDir = $themesDir . DIRECTORY_SEPARATOR . '.mks-staging-' . bin2hex(random_bytes(4)) . '-' . $themeIdentifier;

        $extractedRoot = ArchiveExtractor::extract($zipPath, $stagingDir);
        $log->step('extract', "root={$extractedRoot}");
        $steps[] = 'extract';

        try {
            $themeJsonPath = $extractedRoot . DIRECTORY_SEPARATOR . 'theme.json';
            if (! is_file($themeJsonPath)) {
                throw UpdateException::validation('theme.json not found at root of extracted content.');
            }

            $json = json_decode((string) file_get_contents($themeJsonPath), true);
            if (! is_array($json) || empty($json['name'])) {
                throw UpdateException::validation('Invalid theme.json: missing "name".');
            }

            // Identity guard — ZIP's identifier (root folder OR slugified name) must match target.
            $rootFolderName = basename($extractedRoot);
            $zipIdentifier = $rootFolderName !== basename($stagingDir)
                ? $rootFolderName
                : strtolower(str_replace([' ', '_'], '-', (string) $json['name']));

            if ($zipIdentifier !== $themeIdentifier) {
                throw UpdateException::validation(
                    "ZIP theme identifier '{$zipIdentifier}' does not match target '{$themeIdentifier}'."
                );
            }

            $toVersion = (string) ($json['version'] ?? '0.0.0');
            if (! $force) {
                $cmp = version_compare($toVersion, $fromVersion);
                if ($cmp === 0) {
                    throw UpdateException::validation("Theme '{$themeIdentifier}' is already at version {$fromVersion}. Use --force on CLI to reinstall.");
                }
                if ($cmp < 0) {
                    throw UpdateException::validation("Downgrade rejected: {$fromVersion} -> {$toVersion}. Use --force on CLI to override.");
                }
            }

            // dist/ is REQUIRED — production servers have no npm to build it.
            $distPath = $extractedRoot . DIRECTORY_SEPARATOR . 'dist';
            if (! is_dir($distPath)) {
                throw UpdateException::validation(
                    'Theme ZIP is missing dist/. Production servers cannot build assets — include pre-built dist/ in the archive.'
                );
            }

            $log->step('validate-manifest', "{$fromVersion} -> {$toVersion}");
            $steps[] = 'validate-manifest';

            // Swap.
            $backupManager = new BackupManager(UpdateTarget::Theme, $themeIdentifier);
            $backupManager->ensureRoot($current->path);
            $backupPath = $backupManager->newBackupPath($current->path, $fromVersion);

            $replacer = new AtomicReplacer;
            $replacer->swap($extractedRoot, $current->path, $backupPath);
            $actualBackup = $replacer->backupPath();
            $this->cleanupStagingRemnants($stagingDir);

            $log->step('swap', "backup={$actualBackup}");
            $steps[] = 'swap';

            // Post-steps.
            $this->themeManager->clearCache();
            $log->step('clear-theme-cache');
            $steps[] = 'clear-theme-cache';

            try {
                $this->themeManager->publishAssets($themeIdentifier);
                $log->step('publish-assets');
                $steps[] = 'publish-assets';
            } catch (\Throwable $e) {
                $log->warning('publishAssets failed: ' . $e->getMessage());
                $warnings[] = 'Theme asset publish failed: ' . $e->getMessage();
            }

            try {
                Artisan::call('mks:theme-publish-lang', ['--theme' => $themeIdentifier]);
                $log->step('publish-lang');
                $steps[] = 'publish-lang';
            } catch (\Throwable $e) {
                $log->warning('theme-publish-lang failed: ' . $e->getMessage());
                $warnings[] = 'Theme translation publish failed: ' . $e->getMessage();
            }

            try {
                Artisan::call('optimize:clear');
                $log->step('optimize-clear');
                $steps[] = 'optimize-clear';
            } catch (\Throwable $e) {
                $log->warning('optimize:clear failed: ' . $e->getMessage());
            }

            $keep = (int) config('mksine.updater.keep_backups', 3);
            $pruned = $backupManager->prune($current->path, $keep);
            if ($pruned !== []) {
                $log->info('Pruned ' . count($pruned) . ' old backup(s).');
            }

            return [
                'from' => $fromVersion,
                'to' => $toVersion,
                'backup' => $actualBackup,
            ];
        } finally {
            if (is_dir($stagingDir)) {
                try {
                    ArchiveExtractor::deleteDirectory($stagingDir);
                } catch (\Throwable) {
                    // Non-fatal.
                }
            }
        }
    }

    private function assertMaxZipSize(string $zipPath): void
    {
        $maxMb = (int) config('mksine.updater.max_zip_size_mb', 256);
        $size = @filesize($zipPath) ?: 0;
        if ($size <= 0) {
            throw UpdateException::validation('Uploaded ZIP is empty or unreadable.');
        }
        if ($size > $maxMb * 1024 * 1024) {
            throw UpdateException::validation("Uploaded ZIP exceeds max size of {$maxMb} MB.");
        }
    }

    private function cleanupStagingRemnants(string $stagingDir): void
    {
        if (is_dir($stagingDir)) {
            try {
                ArchiveExtractor::deleteDirectory($stagingDir);
            } catch (\Throwable) {
                // Non-fatal.
            }
        }
    }
}
