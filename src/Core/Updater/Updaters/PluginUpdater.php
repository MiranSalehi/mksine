<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater\Updaters;

use Miran\Mksine\Core\Plugins\PluginManager;
use Miran\Mksine\Core\Plugins\PluginManifest;
use Miran\Mksine\Core\Updater\ArchiveExtractor;
use Miran\Mksine\Core\Updater\ArtisanCaller;
use Miran\Mksine\Core\Updater\AtomicReplacer;
use Miran\Mksine\Core\Updater\BackupManager;
use Miran\Mksine\Core\Updater\Support\RuntimeCache;
use Miran\Mksine\Core\Updater\UpdateContext;
use Miran\Mksine\Core\Updater\UpdateException;
use Miran\Mksine\Core\Updater\UpdateLog;
use Miran\Mksine\Core\Updater\UpdateResult;
use Miran\Mksine\Core\Updater\UpdateRunner;
use Miran\Mksine\Core\Updater\UpdateTarget;
use Miran\Mksine\Core\Updater\VersionGuard;
use Miran\Mksine\Models\Plugin as PluginModel;
use Miran\Mksine\Support\UploadLimits;

/**
 * Updates an installed project plugin from a ZIP upload.
 *
 * Active plugins are demoted in the DB only (the plugin deactivate() hook is
 * not invoked). After a successful swap the row stays `installed` so the
 * operator re-activates on a subsequent request.
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
            function (UpdateLog $log, UpdateContext $ctx) use ($pluginId, $zipPath, $force): void {
                $this->execute($pluginId, $zipPath, $force, $log, $ctx);
            }
        );
    }

    private function execute(
        string $pluginId,
        string $zipPath,
        bool $force,
        UpdateLog $log,
        UpdateContext $ctx,
    ): void {
        $currentManifest = $this->pluginManager->getManifest($pluginId);
        if ($currentManifest === null) {
            throw UpdateException::validation("Plugin '{$pluginId}' is not discovered. Only installed plugins can be updated.");
        }

        $currentPath = $currentManifest->basePath();
        $pluginsDir = realpath(base_path($this->pluginsPathConfig()));
        $currentPathReal = realpath($currentPath);

        if ($pluginsDir === false || $currentPathReal === false || ! str_starts_with($currentPathReal, $pluginsDir.DIRECTORY_SEPARATOR)) {
            throw UpdateException::validation(
                "Plugin '{$pluginId}' is not a project plugin. Composer-installed plugins must be updated via composer on the dev machine."
            );
        }

        $fromVersion = $currentManifest->version();
        $ctx->fromVersion = $fromVersion;
        $log->step('validate-zip', "zip={$zipPath}");
        $ctx->steps[] = 'validate-zip';

        $stagingDir = $pluginsDir.DIRECTORY_SEPARATOR.'.mks-staging-'.bin2hex(random_bytes(4)).'-'.$pluginId;
        $this->assertMaxZipSize($zipPath);

        $extractedRoot = ArchiveExtractor::extract($zipPath, $stagingDir);
        $log->step('extract', "root={$extractedRoot}");
        $ctx->steps[] = 'extract';

        $wasActive = false;

        try {
            $newManifest = $this->loadManifest($extractedRoot);
            $toVersion = $newManifest->version();
            $ctx->toVersion = $toVersion;

            if ($newManifest->id() !== $pluginId) {
                throw UpdateException::validation(
                    "ZIP plugin id '{$newManifest->id()}' does not match target '{$pluginId}'."
                );
            }

            VersionGuard::assertUpgrade($fromVersion, $toVersion, $force);
            $this->assertPluginVendorPresent($extractedRoot);

            $log->step('validate-manifest', "{$fromVersion} -> {$toVersion}");
            $ctx->steps[] = 'validate-manifest';

            $wasActive = $this->quiesceIfActive($pluginId, $log, $ctx);

            $backupManager = new BackupManager(UpdateTarget::Plugin, $pluginId);
            $backupManager->ensureRoot($currentPath);
            $backupPath = $backupManager->newBackupPath($currentPath, $fromVersion);

            $replacer = new AtomicReplacer;
            try {
                $replacer->swap($extractedRoot, $currentPath, $backupPath);
            } catch (UpdateException $e) {
                if ($wasActive) {
                    $this->restoreActiveStatus($pluginId);
                }
                throw $e;
            }

            $ctx->backupPath = $replacer->backupPath();
            $ctx->swapped = true;
            $this->cleanupStagingRemnants($stagingDir);
            RuntimeCache::resetOpcache();

            $log->step('swap', 'backup='.($ctx->backupPath ?? 'none'));
            $ctx->steps[] = 'swap';

            $this->runPostSteps($pluginId, $log, $ctx);

            $this->runMigrations($pluginId, $log, $ctx);

            $this->stampModelOnSuccess($pluginId, $wasActive);
            $log->step('status', $wasActive ? 'installed (was active — reactivate manually next request)' : 'installed (was not active)');
            $ctx->steps[] = 'status';

            $keep = (int) config('mksine.updater.keep_backups', 3);
            $pruned = $backupManager->prune($currentPath, $keep);
            if ($pruned !== []) {
                $log->info('Pruned '.count($pruned).' old backup(s).');
            }

            if ($wasActive) {
                $ctx->warnings[] = 'Plugin was active before the update. Re-activate it from the Plugins page so the new code boots with a fresh autoloader.';
            }
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

    private function pluginsPathConfig(): string
    {
        $raw = config('mksine.plugins_path', 'plugins');

        return is_string($raw) && $raw !== '' ? $raw : 'plugins';
    }

    private function assertMaxZipSize(string $zipPath): void
    {
        $maxMb = UploadLimits::updaterMaxZipMb();
        $size = @filesize($zipPath) ?: 0;
        if ($size <= 0) {
            throw UpdateException::validation('Uploaded ZIP is empty or unreadable.');
        }
        if ($size > $maxMb * 1024 * 1024) {
            throw UpdateException::validation("Uploaded ZIP exceeds max size of {$maxMb} MB.");
        }
    }

    private function assertPluginVendorPresent(string $extractedRoot): void
    {
        $composerFile = $extractedRoot.DIRECTORY_SEPARATOR.'composer.json';
        if (! is_file($composerFile)) {
            return;
        }

        $decoded = json_decode((string) file_get_contents($composerFile), true);
        if (! is_array($decoded)) {
            throw UpdateException::validation('plugin composer.json is invalid JSON.');
        }

        $require = is_array($decoded['require'] ?? null) ? $decoded['require'] : [];
        $packages = [];
        foreach ($require as $package => $constraint) {
            if (! is_string($package)) {
                continue;
            }
            $lower = strtolower($package);
            if ($lower === 'php' || str_starts_with($lower, 'ext-')) {
                continue;
            }
            $packages[] = $package;
        }

        if ($packages === []) {
            return;
        }

        if (! is_dir($extractedRoot.DIRECTORY_SEPARATOR.'vendor')) {
            throw UpdateException::validation(
                'Plugin ZIP declares Composer packages ('.implode(', ', $packages).') but has no vendor/ directory. Production servers cannot run composer — vendor the dependencies into the ZIP.'
            );
        }
    }

    private function loadManifest(string $extractedRoot): PluginManifest
    {
        try {
            return PluginManifest::fromPath($extractedRoot);
        } catch (\InvalidArgumentException $e) {
            throw UpdateException::validation('Invalid plugin.php in ZIP: '.$e->getMessage(), $e);
        }
    }

    private function quiesceIfActive(string $pluginId, UpdateLog $log, UpdateContext $ctx): bool
    {
        $model = PluginModel::where('plugin_id', $pluginId)->first();
        $wasActive = $model?->isActive() ?? false;

        if (! $wasActive || $model === null) {
            return false;
        }

        $model->update([
            'status' => PluginModel::STATUS_INSTALLED,
            'deactivated_at' => now(),
        ]);
        $log->step('quiesce-db');
        $ctx->steps[] = 'quiesce-db';

        return true;
    }

    private function restoreActiveStatus(string $pluginId): void
    {
        PluginModel::where('plugin_id', $pluginId)->update([
            'status' => PluginModel::STATUS_ACTIVE,
            'deactivated_at' => null,
        ]);
    }

    private function runPostSteps(string $pluginId, UpdateLog $log, UpdateContext $ctx): void
    {
        app()->forgetInstance(PluginManager::class);
        $output = ArtisanCaller::callOrFail('mks-plugin:discover');
        if ($output !== '') {
            $log->info('discover output: '.str_replace(["\r", "\n"], [' ', ' '], $output));
        }
        $log->step('discover');
        $ctx->steps[] = 'discover';

        ArtisanCaller::callOrFail('mks-plugin:publish-lang', ['plugin' => $pluginId]);
        $log->step('publish-lang');
        $ctx->steps[] = 'publish-lang';

        ArtisanCaller::callOrFail('mks-plugin:publish', ['plugin' => $pluginId, '--force' => true]);
        $log->step('publish-assets');
        $ctx->steps[] = 'publish-assets';

        try {
            ArtisanCaller::callOrFail('optimize:clear');
            $log->step('optimize-clear');
            $ctx->steps[] = 'optimize-clear';
        } catch (UpdateException $e) {
            $log->warning('optimize:clear failed: '.$e->getMessage());
            $ctx->warnings[] = 'optimize:clear failed after swap.';
        }
    }

    private function runMigrations(string $pluginId, UpdateLog $log, UpdateContext $ctx): void
    {
        $ctx->dbPossiblyDirty = true;

        try {
            $output = ArtisanCaller::callOrFail('mks-plugin:migrate', ['plugin' => $pluginId]);
            if ($output !== '') {
                $log->info('migrate output: '.str_replace(["\r", "\n"], [' ', ' '], $output));
            }
            $log->step('migrate');
            $ctx->steps[] = 'migrate';
            $ctx->dbPossiblyDirty = false;
        } catch (UpdateException $e) {
            $this->markPluginDegraded(
                $pluginId,
                "Update migration failed — plugin marked as inactive (boot_failed=true). Inspect storage/logs/mksine-updates/ and run `php artisan mks-plugin:migrate {$pluginId}` manually."
            );
            $log->warning('Plugin marked as boot_failed/inactive. Manual migration recovery required.');
            $ctx->warnings[] = 'Migrations failed after swap: '.$e->getMessage();

            throw UpdateException::post(
                "Plugin '{$pluginId}' updated to ".($ctx->toVersion ?? '?').' but migrations failed. Plugin is now INACTIVE. See log: '.$log->path(),
                $e
            );
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

    private function stampModelOnSuccess(string $pluginId, bool $wasActive): void
    {
        $model = PluginModel::firstOrCreate(
            ['plugin_id' => $pluginId],
            ['status' => PluginModel::STATUS_INSTALLED, 'installed_at' => now()]
        );

        if ($model->hasBootFailed()) {
            $model->clearBootFailure();
        }

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
