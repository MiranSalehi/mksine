<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater;

use Miran\Mksine\Core\Plugins\PluginManager;
use Miran\Mksine\Core\Theme\ThemeManager as ThemeManagerService;
use Miran\Mksine\Models\Plugin as PluginModel;

/**
 * Restores the most recent backup for a plugin or theme ZIP update.
 *
 * Backups live next to the target under .mks-backups/{id}-{TS}[-v{ver}].
 * Rollback restores CODE ONLY. Migrations are not reversed.
 */
final class RollbackManager
{
    public function rollbackPlugin(string $pluginId): UpdateResult
    {
        return (new UpdateRunner)->run(
            UpdateTarget::Plugin,
            $pluginId,
            function (UpdateLog $log, UpdateContext $ctx) use ($pluginId): void {
                $manager = app(PluginManager::class);
                $manifest = $manager->getManifest($pluginId);
                if ($manifest === null) {
                    throw UpdateException::validation("Plugin '{$pluginId}' not discovered.");
                }

                $targetPath = $manifest->basePath();
                $pluginsDir = realpath(base_path(config('mksine.plugins_path', 'plugins')));
                if ($pluginsDir === false || ! str_starts_with(realpath($targetPath) ?: '', $pluginsDir.DIRECTORY_SEPARATOR)) {
                    throw UpdateException::validation("Plugin '{$pluginId}' is not a project plugin.");
                }

                $fromVersion = $manifest->version();
                $ctx->fromVersion = $fromVersion;

                [$restoredFrom, $newVersion] = $this->restore(UpdateTarget::Plugin, $pluginId, $targetPath, $fromVersion, $log, $ctx);
                $ctx->toVersion = $newVersion;
                $ctx->backupPath = $restoredFrom;

                $model = PluginModel::where('plugin_id', $pluginId)->first();
                if ($model !== null) {
                    $model->update(['status' => PluginModel::STATUS_INSTALLED]);
                    if ($model->hasBootFailed()) {
                        $model->clearBootFailure();
                    }
                }

                $ctx->warnings[] = 'Rollback restored CODE only. Any migrations applied after the backup were NOT reversed.';
            }
        );
    }

    public function rollbackTheme(string $themeIdentifier): UpdateResult
    {
        return (new UpdateRunner)->run(
            UpdateTarget::Theme,
            $themeIdentifier,
            function (UpdateLog $log, UpdateContext $ctx) use ($themeIdentifier): void {
                $themeManager = app(ThemeManagerService::class);
                $theme = $themeManager->get($themeIdentifier);
                if ($theme === null) {
                    throw UpdateException::validation("Theme '{$themeIdentifier}' not discovered.");
                }
                if ($theme->isPackageTheme()) {
                    throw UpdateException::validation("Theme '{$themeIdentifier}' is a package theme — cannot roll back.");
                }

                $ctx->fromVersion = $theme->version;

                [$restoredFrom, $newVersion] = $this->restore(UpdateTarget::Theme, $themeIdentifier, $theme->path, $theme->version, $log, $ctx);
                $ctx->toVersion = $newVersion;
                $ctx->backupPath = $restoredFrom;

                $themeManager->clearCache();
                try {
                    $themeManager->publishAssets($themeIdentifier);
                    $log->step('publish-assets');
                    $ctx->steps[] = 'publish-assets';
                } catch (\Throwable $e) {
                    $ctx->warnings[] = 'publishAssets failed after rollback: '.$e->getMessage();
                }
            }
        );
    }

    /**
     * @return array{0: string, 1: string}  [restored-backup-path, new-version-string]
     */
    private function restore(
        UpdateTarget $target,
        string $identifier,
        string $targetPath,
        string $currentVersion,
        UpdateLog $log,
        UpdateContext $ctx,
    ): array {
        $backupManager = new BackupManager($target, $identifier);
        $latest = $backupManager->latestBackup($targetPath);
        if ($latest === null) {
            throw UpdateException::validation("No backup found for {$target->value}:{$identifier}.");
        }

        $log->info('Restoring backup: '.$latest);
        $ctx->steps[] = 'locate-backup';

        $abandoned = $targetPath.'.failed-'.date('Ymd-His');
        if (file_exists($targetPath) && ! @rename($targetPath, $abandoned)) {
            throw UpdateException::replace("Unable to move current target aside: {$targetPath}");
        }

        if (! @rename($latest, $targetPath)) {
            if (is_dir($abandoned)) {
                @rename($abandoned, $targetPath);
            }
            throw UpdateException::replace("Unable to restore backup: {$latest} -> {$targetPath}");
        }

        $log->step('swap-back');
        $ctx->steps[] = 'swap-back';
        $ctx->swapped = true;

        $newVersion = $this->readVersionFromTarget($target, $targetPath) ?? $currentVersion;

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
            UpdateTarget::Plugin => $this->readPhpManifestVersion($targetPath.'/plugin.php'),
            UpdateTarget::Theme => $this->readJsonVersion($targetPath.'/theme.json'),
            UpdateTarget::Core => null,
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
}
