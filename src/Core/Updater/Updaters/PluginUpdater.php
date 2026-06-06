<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater\Updaters;

use Illuminate\Support\Facades\Artisan;
use Miran\Mksine\Core\Plugins\PluginManager;
use Miran\Mksine\Core\Plugins\PluginManifest;
use Miran\Mksine\Core\Updater\ArchiveExtractor;
use Miran\Mksine\Core\Updater\AtomicReplacer;
use Miran\Mksine\Core\Updater\BackupManager;
use Miran\Mksine\Core\Updater\UpdateException;
use Miran\Mksine\Core\Updater\UpdateLog;
use Miran\Mksine\Core\Updater\UpdateResult;
use Miran\Mksine\Core\Updater\UpdateRunner;
use Miran\Mksine\Core\Updater\UpdateTarget;
use Miran\Mksine\Models\Plugin as PluginModel;

/**
 * Updates an installed project plugin from a ZIP upload.
 *
 * Pipeline order (publish-first, migrate-last):
 *   1. Lock.
 *   2. Validate ZIP: safe extract -> staging under plugins/ parent FS.
 *   3. Validate manifest: plugin.php exists, id matches, version > current.
 *   4. If plugin is active, call deactivate() on the OLD instance (same request).
 *   5. Atomic rename swap:  current -> backup, staging -> current.
 *   6. Post-steps (publish-lang, publish assets, discover, clear caches).
 *   7. Migrate LAST. On failure: mark boot_failed + status=inactive. Keep new code.
 *      We deliberately do NOT auto-rollback migrations — forward migrations are
 *      assumed backward-compatible per plugin authoring guidelines.
 *   8. Status = installed. User manually re-activates in the NEXT request so the
 *      autoloader picks up the new classes cleanly.
 *
 * Only plugins living under base_path('plugins') are updatable — composer-installed
 * mks-plugin packages must be updated through composer on the dev machine and deployed.
 */
final class PluginUpdater
{
    public function __construct(
        private readonly UpdateRunner $runner,
        private readonly PluginManager $pluginManager,
    ) {}

    public function update(string $pluginId, string $zipPath, bool $force = false): UpdateResult
    {
        return $this->runner->run(
            UpdateTarget::Plugin,
            $pluginId,
            function (UpdateLog $log, array &$steps, array &$warnings) use ($pluginId, $zipPath, $force): array {
                return $this->execute($pluginId, $zipPath, $force, $log, $steps, $warnings);
            }
        );
    }

    /**
     * @param  array<int,string>  $steps
     * @param  array<int,string>  $warnings
     * @return array{from: ?string, to: string, backup: ?string}
     */
    private function execute(
        string $pluginId,
        string $zipPath,
        bool $force,
        UpdateLog $log,
        array &$steps,
        array &$warnings,
    ): array {
        // 1) Locate current plugin on disk. Only project plugins are updatable.
        $currentManifest = $this->pluginManager->getManifest($pluginId);
        if ($currentManifest === null) {
            throw UpdateException::validation("Plugin '{$pluginId}' is not discovered. Only installed plugins can be updated.");
        }

        $currentPath = $currentManifest->basePath();
        $pluginsDir = realpath(base_path($this->pluginsPathConfig()));
        $currentPathReal = realpath($currentPath);

        if ($pluginsDir === false || $currentPathReal === false || ! str_starts_with($currentPathReal, $pluginsDir . DIRECTORY_SEPARATOR)) {
            throw UpdateException::validation(
                "Plugin '{$pluginId}' is not a project plugin. Composer-installed plugins must be updated via composer on the dev machine."
            );
        }

        $fromVersion = $currentManifest->version();
        $log->step('validate-zip', "zip={$zipPath}");
        $steps[] = 'validate-zip';

        // 2) Extract to staging (same filesystem as plugins/ so rename is atomic).
        $stagingParent = $pluginsDir;
        $stagingDir = $stagingParent . DIRECTORY_SEPARATOR . '.mks-staging-' . bin2hex(random_bytes(4)) . '-' . $pluginId;
        $this->assertMaxZipSize($zipPath);

        $extractedRoot = ArchiveExtractor::extract($zipPath, $stagingDir);
        $log->step('extract', "root={$extractedRoot}");
        $steps[] = 'extract';

        try {
            // 3) Validate new manifest.
            $newManifest = $this->loadManifest($extractedRoot);
            $toVersion = $newManifest->version();

            if ($newManifest->id() !== $pluginId) {
                throw UpdateException::validation(
                    "ZIP plugin id '{$newManifest->id()}' does not match target '{$pluginId}'."
                );
            }

            if (! $force) {
                $cmp = version_compare($toVersion, $fromVersion);
                if ($cmp === 0) {
                    throw UpdateException::validation(
                        "Plugin '{$pluginId}' is already at version {$fromVersion}. Use --force on CLI to reinstall."
                    );
                }
                if ($cmp < 0) {
                    throw UpdateException::validation(
                        "Downgrade rejected: {$fromVersion} -> {$toVersion}. Use --force on CLI to override."
                    );
                }
            }

            $log->step('validate-manifest', "{$fromVersion} -> {$toVersion}");
            $steps[] = 'validate-manifest';

            // 4) Deactivate the currently-running instance so its resources are released.
            $wasActive = $this->deactivateIfActive($pluginId, $log, $warnings);

            // 5) Swap.
            $backupManager = new BackupManager(UpdateTarget::Plugin, $pluginId);
            $backupManager->ensureRoot($currentPath);
            $backupPath = $backupManager->newBackupPath($currentPath, $fromVersion);

            $replacer = new AtomicReplacer;
            $replacer->swap($extractedRoot, $currentPath, $backupPath);
            $actualBackupPath = $replacer->backupPath();

            // If the extracted root was a sub-folder of staging, the rest of the staging
            // parent is now empty — clean it up so we don't leak.
            $this->cleanupStagingRemnants($stagingDir);

            $log->step('swap', "backup={$actualBackupPath}");
            $steps[] = 'swap';

            // 6) Post-steps (publish-first; safe even if plugin isn't active).
            $this->runPostSteps($pluginId, $log, $steps, $warnings);

            // 7) Migrate LAST.
            $migrateOk = $this->runMigrations($pluginId, $log, $warnings);

            if (! $migrateOk) {
                $this->markPluginDegraded(
                    $pluginId,
                    "Update migration failed — plugin marked as inactive (boot_failed=true). Inspect storage/logs/mksine-updates/ and run `php artisan mks-plugin:migrate {$pluginId}` manually."
                );
                $log->warning('Plugin marked as boot_failed/inactive. Manual migration recovery required.');

                throw UpdateException::post(
                    "Plugin '{$pluginId}' updated to {$toVersion} but migrations failed. Plugin is now INACTIVE. See log: " . $log->path()
                );
            }

            // 8) Record state: never auto-activate. User re-activates in next request.
            $this->stampModelOnSuccess($pluginId, $wasActive, $toVersion);
            $log->step('status', $wasActive ? 'installed (was active — reactivate manually next request)' : 'installed (was not active)');
            $steps[] = 'status';

            // Prune old backups.
            $keep = (int) config('mksine.updater.keep_backups', 3);
            $pruned = $backupManager->prune($currentPath, $keep);
            if ($pruned !== []) {
                $log->info('Pruned ' . count($pruned) . ' old backup(s).');
            }

            if ($wasActive) {
                $warnings[] = 'Plugin was active before the update. Re-activate it from the Plugins page so the new code boots with a fresh autoloader.';
            }

            return [
                'from' => $fromVersion,
                'to' => $toVersion,
                'backup' => $actualBackupPath,
            ];
        } finally {
            // Ensure staging is gone no matter what.
            if (is_dir($stagingDir)) {
                try {
                    ArchiveExtractor::deleteDirectory($stagingDir);
                } catch (\Throwable) {
                    // Non-fatal.
                }
            }
        }
    }

    private function pluginsPathConfig(): string
    {
        $raw = config('mksine.plugins_path', 'plugins');

        return is_string($raw) && $raw !== '' ? $raw : 'plugins';
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

    private function loadManifest(string $extractedRoot): PluginManifest
    {
        try {
            return PluginManifest::fromPath($extractedRoot);
        } catch (\InvalidArgumentException $e) {
            throw UpdateException::validation('Invalid plugin.php in ZIP: ' . $e->getMessage(), $e);
        }
    }

    private function deactivateIfActive(string $pluginId, UpdateLog $log, array &$warnings): bool
    {
        $model = PluginModel::where('plugin_id', $pluginId)->first();
        $wasActive = $model?->isActive() ?? false;

        if (! $wasActive) {
            return false;
        }

        try {
            $this->pluginManager->deactivate($pluginId);
            $log->step('deactivate-old');
        } catch (\Throwable $e) {
            $log->warning('deactivate() of old plugin threw: ' . $e->getMessage());
            $warnings[] = 'Old plugin deactivate() threw; continuing with replace. Inspect plugin log for details.';
            // We still mark the DB row as inactive so state is consistent.
            $model?->update(['status' => PluginModel::STATUS_INACTIVE, 'deactivated_at' => now()]);
        }

        return true;
    }

    /**
     * @param  array<int,string>  $steps
     * @param  array<int,string>  $warnings
     */
    private function runPostSteps(string $pluginId, UpdateLog $log, array &$steps, array &$warnings): void
    {
        // Force fresh discovery so PluginManager sees the new files.
        try {
            app()->forgetInstance(PluginManager::class);
            Artisan::call('mks-plugin:discover');
            $log->step('discover');
            $steps[] = 'discover';
        } catch (\Throwable $e) {
            $log->warning('discover failed: ' . $e->getMessage());
            $warnings[] = 'mks-plugin:discover failed after swap: ' . $e->getMessage();
        }

        try {
            Artisan::call('mks-plugin:publish-lang');
            $log->step('publish-lang');
            $steps[] = 'publish-lang';
        } catch (\Throwable $e) {
            $log->warning('publish-lang failed: ' . $e->getMessage());
            $warnings[] = 'mks-plugin:publish-lang failed after swap.';
        }

        try {
            Artisan::call('mks-plugin:publish', ['plugin' => $pluginId, '--force' => true]);
            $log->step('publish-assets');
            $steps[] = 'publish-assets';
        } catch (\Throwable $e) {
            $log->warning('publish assets failed: ' . $e->getMessage());
            $warnings[] = 'mks-plugin:publish failed after swap.';
        }

        try {
            Artisan::call('optimize:clear');
            $log->step('optimize-clear');
            $steps[] = 'optimize-clear';
        } catch (\Throwable $e) {
            $log->warning('optimize:clear failed: ' . $e->getMessage());
            $warnings[] = 'optimize:clear failed after swap.';
        }
    }

    private function runMigrations(string $pluginId, UpdateLog $log, array &$warnings): bool
    {
        try {
            Artisan::call('mks-plugin:migrate', ['plugin' => $pluginId]);
            $output = trim(Artisan::output());
            if ($output !== '') {
                $log->info('migrate output: ' . str_replace(["\r", "\n"], [' ', ' '], $output));
            }
            $log->step('migrate');

            return true;
        } catch (\Throwable $e) {
            $log->error('migrate failed: ' . $e->getMessage());
            $warnings[] = 'Migrations failed after swap: ' . $e->getMessage();

            return false;
        }
    }

    private function markPluginDegraded(string $pluginId, string $error): void
    {
        $model = PluginModel::firstOrCreate(
            ['plugin_id' => $pluginId],
            ['status' => PluginModel::STATUS_INACTIVE, 'installed_at' => now()]
        );
        $model->markBootFailed($error);
    }

    private function stampModelOnSuccess(string $pluginId, bool $wasActive, string $toVersion): void
    {
        $model = PluginModel::firstOrCreate(
            ['plugin_id' => $pluginId],
            ['status' => PluginModel::STATUS_INSTALLED, 'installed_at' => now()]
        );

        // Drop the boot-failed flag because code + DB are now consistent.
        if ($model->hasBootFailed()) {
            $model->clearBootFailure();
        }

        // Always demote to 'installed' so next request re-activates with a clean autoloader.
        $payload = ['status' => PluginModel::STATUS_INSTALLED];
        if ($wasActive) {
            $payload['deactivated_at'] = now();
        }
        $model->update($payload);
    }

    private function cleanupStagingRemnants(string $stagingDir): void
    {
        if (! is_dir($stagingDir)) {
            return;
        }

        try {
            ArchiveExtractor::deleteDirectory($stagingDir);
        } catch (\Throwable) {
            // Non-fatal.
        }
    }
}
