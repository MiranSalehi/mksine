<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater;

use Miran\Mksine\Core\Plugins\PluginManager;
use Miran\Mksine\Core\Theme\ThemeManager as ThemeManagerService;
use Miran\Mksine\Models\Plugin as PluginModel;

/**
 * Restores the most recent backup for a given update target.
 *
 * Backups live next to the target under .mks-backups/{id}-{TS}[-v{ver}].
 * The most recently-created backup (highest mtime) wins.
 *
 * IMPORTANT: rollback only restores CODE. It does NOT reverse migrations.
 * Forward migrations must be backward-compatible by convention. If the new
 * migration was destructive, operators must restore the DB from a snapshot
 * separately before calling rollback.
 */
final class RollbackManager
{
    public function rollbackPlugin(string $pluginId): UpdateResult
    {
        return (new UpdateRunner)->run(
            UpdateTarget::Plugin,
            $pluginId,
            function (UpdateLog $log, array &$steps, array &$warnings) use ($pluginId): array {
                $manager = app(PluginManager::class);
                $manifest = $manager->getManifest($pluginId);
                if ($manifest === null) {
                    throw UpdateException::validation("Plugin '{$pluginId}' not discovered.");
                }

                $targetPath = $manifest->basePath();
                $pluginsDir = realpath(base_path(config('mksine.plugins_path', 'plugins')));
                if ($pluginsDir === false || ! str_starts_with(realpath($targetPath) ?: '', $pluginsDir . DIRECTORY_SEPARATOR)) {
                    throw UpdateException::validation("Plugin '{$pluginId}' is not a project plugin.");
                }

                $fromVersion = $manifest->version();

                [$restoredFrom, $newVersion] = $this->restore(UpdateTarget::Plugin, $pluginId, $targetPath, $fromVersion, $log, $steps);

                // Mark plugin as installed (not active) — require fresh re-activation.
                $model = PluginModel::where('plugin_id', $pluginId)->first();
                if ($model !== null) {
                    $model->update(['status' => PluginModel::STATUS_INSTALLED]);
                    if ($model->hasBootFailed()) {
                        $model->clearBootFailure();
                    }
                }

                $warnings[] = 'Rollback restored CODE only. Any migrations applied after the backup were NOT reversed.';

                return [
                    'from' => $fromVersion,
                    'to' => $newVersion,
                    'backup' => $restoredFrom,
                ];
            }
        );
    }

    public function rollbackTheme(string $themeIdentifier): UpdateResult
    {
        return (new UpdateRunner)->run(
            UpdateTarget::Theme,
            $themeIdentifier,
            function (UpdateLog $log, array &$steps, array &$warnings) use ($themeIdentifier): array {
                $themeManager = app(ThemeManagerService::class);
                $theme = $themeManager->get($themeIdentifier);
                if ($theme === null) {
                    throw UpdateException::validation("Theme '{$themeIdentifier}' not discovered.");
                }
                if ($theme->isPackageTheme()) {
                    throw UpdateException::validation("Theme '{$themeIdentifier}' is a package theme — cannot roll back.");
                }

                [$restoredFrom, $newVersion] = $this->restore(UpdateTarget::Theme, $themeIdentifier, $theme->path, $theme->version, $log, $steps);

                $themeManager->clearCache();
                try {
                    $themeManager->publishAssets($themeIdentifier);
                    $log->step('publish-assets');
                    $steps[] = 'publish-assets';
                } catch (\Throwable $e) {
                    $warnings[] = 'publishAssets failed after rollback: ' . $e->getMessage();
                }

                return [
                    'from' => $theme->version,
                    'to' => $newVersion,
                    'backup' => $restoredFrom,
                ];
            }
        );
    }

    public function rollbackCore(): UpdateResult
    {
        return (new UpdateRunner)->run(
            UpdateTarget::Core,
            'mksine-core',
            function (UpdateLog $log, array &$steps, array &$warnings): array {
                $targetPath = base_path('packages/mksine');
                if (! is_dir($targetPath)) {
                    throw UpdateException::validation('Core path not found: ' . $targetPath);
                }

                $fromVersion = (string) config('mksine.version', '0.0.0');

                [$restoredFrom, $newVersion] = $this->restore(UpdateTarget::Core, 'mksine-core', $targetPath, $fromVersion, $log, $steps);

                try {
                    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                    $log->step('optimize-clear');
                    $steps[] = 'optimize-clear';
                } catch (\Throwable $e) {
                    $warnings[] = 'optimize:clear failed after rollback: ' . $e->getMessage();
                }

                $warnings[] = 'Core rollback restored CODE only. DB migrations applied after the backup were NOT reversed.';

                return [
                    'from' => $fromVersion,
                    'to' => $newVersion,
                    'backup' => $restoredFrom,
                ];
            }
        );
    }

    /**
     * @param  array<int,string>  $steps
     * @return array{0: string, 1: string}  [restored-backup-path, new-version-string]
     */
    private function restore(
        UpdateTarget $target,
        string $identifier,
        string $targetPath,
        string $currentVersion,
        UpdateLog $log,
        array &$steps,
    ): array {
        $backupManager = new BackupManager($target, $identifier);
        $latest = $backupManager->latestBackup($targetPath);
        if ($latest === null) {
            throw UpdateException::validation("No backup found for {$target->value}:{$identifier}.");
        }

        $log->info('Restoring backup: ' . $latest);
        $steps[] = 'locate-backup';

        // Move aside the current (broken/new) tree as failed-{TS}, then rename backup into place.
        $abandoned = $targetPath . '.failed-' . date('Ymd-His');
        if (file_exists($targetPath) && ! @rename($targetPath, $abandoned)) {
            throw UpdateException::replace("Unable to move current target aside: {$targetPath}");
        }

        if (! @rename($latest, $targetPath)) {
            // Attempt to restore what we moved aside so we don't leave an empty target.
            if (is_dir($abandoned)) {
                @rename($abandoned, $targetPath);
            }
            throw UpdateException::replace("Unable to restore backup: {$latest} -> {$targetPath}");
        }

        $log->step('swap-back');
        $steps[] = 'swap-back';

        // Read restored version from manifest if possible.
        $newVersion = $this->readVersionFromTarget($target, $targetPath) ?? $currentVersion;

        // Delete the abandoned tree in the background; non-fatal if it fails.
        if (is_dir($abandoned)) {
            try {
                ArchiveExtractor::deleteDirectory($abandoned);
            } catch (\Throwable) {
                // Non-fatal.
            }
        }

        return [$latest, $newVersion];
    }

    private function readVersionFromTarget(UpdateTarget $target, string $targetPath): ?string
    {
        return match ($target) {
            UpdateTarget::Plugin => $this->readPhpManifestVersion($targetPath . '/plugin.php'),
            UpdateTarget::Theme => $this->readJsonVersion($targetPath . '/theme.json'),
            UpdateTarget::Core => $this->readCoreConfigVersion($targetPath . '/config/mksine.php'),
        };
    }

    private function readPhpManifestVersion(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        try {
            $data = include $path;
        } catch (\Throwable) {
            return null;
        }

        if (is_array($data) && isset($data['version']) && is_string($data['version'])) {
            return $data['version'];
        }

        return null;
    }

    private function readJsonVersion(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);

        return is_array($json) && isset($json['version']) && is_string($json['version'])
            ? $json['version']
            : null;
    }

    private function readCoreConfigVersion(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $contents = (string) file_get_contents($path);
        if (preg_match("/'version'\s*=>\s*'([^']+)'/", $contents, $m) === 1) {
            return $m[1];
        }

        return null;
    }
}
