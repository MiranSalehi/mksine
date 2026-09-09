<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater\Updaters;

use Miran\Mksine\Core\Theme\ThemeDependencyChecker;
use Miran\Mksine\Core\Theme\ThemeManager as ThemeManagerService;
use Miran\Mksine\Core\Updater\ArchiveExtractor;
use Miran\Mksine\Core\Updater\ArtisanCaller;
use Miran\Mksine\Core\Updater\AtomicReplacer;
use Miran\Mksine\Core\Updater\BackupManager;
use Miran\Mksine\Core\Updater\Support\RuntimeCache;
use Miran\Mksine\Core\Updater\Support\ThemeZipIdentity;
use Miran\Mksine\Core\Updater\UpdateContext;
use Miran\Mksine\Core\Updater\UpdateException;
use Miran\Mksine\Core\Updater\UpdateLog;
use Miran\Mksine\Core\Updater\UpdateResult;
use Miran\Mksine\Core\Updater\UpdateRunner;
use Miran\Mksine\Core\Updater\UpdateTarget;
use Miran\Mksine\Core\Updater\VersionGuard;
use Miran\Mksine\Support\UploadLimits;

/**
 * Updates an installed project theme from a ZIP upload.
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
            function (UpdateLog $log, UpdateContext $ctx) use ($themeIdentifier, $zipPath, $force): void {
                $this->execute($themeIdentifier, $zipPath, $force, $log, $ctx);
            }
        );
    }

    private function execute(
        string $themeIdentifier,
        string $zipPath,
        bool $force,
        UpdateLog $log,
        UpdateContext $ctx,
    ): void {
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
        if ($themesDir === false || $currentReal === false || ! str_starts_with($currentReal, $themesDir.DIRECTORY_SEPARATOR)) {
            throw UpdateException::validation("Theme '{$themeIdentifier}' path is not inside the project themes directory.");
        }

        $this->assertMaxZipSize($zipPath);

        $fromVersion = $current->version;
        $ctx->fromVersion = $fromVersion;
        $log->step('validate-zip', "zip={$zipPath}");
        $ctx->steps[] = 'validate-zip';

        $stagingDir = $themesDir.DIRECTORY_SEPARATOR.'.mks-staging-'.bin2hex(random_bytes(4)).'-'.$themeIdentifier;

        $extractedRoot = ArchiveExtractor::extract($zipPath, $stagingDir);
        $log->step('extract', "root={$extractedRoot}");
        $ctx->steps[] = 'extract';

        try {
            $themeJsonPath = $extractedRoot.DIRECTORY_SEPARATOR.'theme.json';
            if (! is_file($themeJsonPath)) {
                throw UpdateException::validation('theme.json not found at root of extracted content.');
            }

            $json = json_decode((string) file_get_contents($themeJsonPath), true);
            if (! is_array($json) || empty($json['name'])) {
                throw UpdateException::validation('Invalid theme.json: missing "name".');
            }

            if (! ThemeZipIdentity::matches($extractedRoot, $stagingDir, $json, $themeIdentifier)) {
                $candidates = implode(', ', ThemeZipIdentity::candidates($extractedRoot, $stagingDir, $json));
                throw UpdateException::validation(
                    "ZIP theme identity [{$candidates}] does not match target '{$themeIdentifier}'."
                );
            }

            $toVersion = (string) ($json['version'] ?? '0.0.0');
            $ctx->toVersion = $toVersion;
            VersionGuard::assertUpgrade($fromVersion, $toVersion, $force);

            $distPath = $extractedRoot.DIRECTORY_SEPARATOR.'dist';
            if (! is_dir($distPath)) {
                throw UpdateException::validation(
                    'Theme ZIP is missing dist/. Production servers cannot build assets — include pre-built dist/ in the archive.'
                );
            }

            $log->step('validate-manifest', "{$fromVersion} -> {$toVersion}");
            $ctx->steps[] = 'validate-manifest';

            $backupManager = new BackupManager(UpdateTarget::Theme, $themeIdentifier);
            $backupManager->ensureRoot($current->path);
            $backupPath = $backupManager->newBackupPath($current->path, $fromVersion);

            $replacer = new AtomicReplacer;
            $replacer->swap($extractedRoot, $current->path, $backupPath);
            $ctx->backupPath = $replacer->backupPath();
            $ctx->swapped = true;
            $this->cleanupStagingRemnants($stagingDir);
            RuntimeCache::resetOpcache();

            $log->step('swap', 'backup='.($ctx->backupPath ?? 'none'));
            $ctx->steps[] = 'swap';

            $this->themeManager->clearCache();
            $log->step('clear-theme-cache');
            $ctx->steps[] = 'clear-theme-cache';

            try {
                $this->themeManager->publishAssets($themeIdentifier);
                $log->step('publish-assets');
                $ctx->steps[] = 'publish-assets';
            } catch (\Throwable $e) {
                throw UpdateException::post('Theme asset publish failed: '.$e->getMessage(), $e);
            }

            ArtisanCaller::callOrFail('mks:theme-publish-lang', ['theme' => $themeIdentifier]);
            $log->step('publish-lang');
            $ctx->steps[] = 'publish-lang';

            try {
                ArtisanCaller::callOrFail('optimize:clear');
                $log->step('optimize-clear');
                $ctx->steps[] = 'optimize-clear';
            } catch (UpdateException $e) {
                $log->warning('optimize:clear failed: '.$e->getMessage());
                $ctx->warnings[] = 'optimize:clear failed after swap.';
            }

            $this->warnMissingPluginDependencies($themeIdentifier, $ctx);

            $keep = (int) config('mksine.updater.keep_backups', 3);
            $pruned = $backupManager->prune($current->path, $keep);
            if ($pruned !== []) {
                $log->info('Pruned '.count($pruned).' old backup(s).');
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

    private function warnMissingPluginDependencies(string $themeIdentifier, UpdateContext $ctx): void
    {
        try {
            $theme = $this->themeManager->get($themeIdentifier);
            if ($theme === null) {
                return;
            }

            $missing = app(ThemeDependencyChecker::class)->missingPluginLabels($theme);
            if ($missing === []) {
                return;
            }

            $ctx->warnings[] = 'Theme requires inactive or missing plugin(s): '.implode(', ', $missing).'.';
        } catch (\Throwable $e) {
            $ctx->warnings[] = 'Could not verify theme plugin dependencies: '.$e->getMessage();
        }
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
