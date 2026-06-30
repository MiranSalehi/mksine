<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Updater\Updaters;

use Illuminate\Console\Application;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Foundation\Console\OptimizeClearCommand;
use Illuminate\Foundation\Console\VendorPublishCommand;
use Illuminate\Support\Facades\Artisan;
use Miran\Mksine\Core\Updater\ArchiveExtractor;
use Miran\Mksine\Core\Updater\AtomicReplacer;
use Miran\Mksine\Core\Updater\BackupManager;
use Miran\Mksine\Core\Updater\Support\ComposerDependencyDiff;
use Miran\Mksine\Core\Updater\UpdateException;
use Miran\Mksine\Core\Updater\UpdateLog;
use Miran\Mksine\Core\Updater\UpdateResult;
use Miran\Mksine\Core\Updater\UpdateRunner;
use Miran\Mksine\Core\Updater\UpdateTarget;
use Miran\Mksine\Support\UploadLimits;

/**
 * Updates the core miran/mksine package in-place from a ZIP upload.
 *
 * Constraints:
 *   - The project MUST use the path-repository install layout
 *     (base_path('packages/mksine')). Package installs from Packagist are
 *     rejected — update those via composer on the dev machine.
 *   - The uploaded ZIP's composer.json MUST declare name=miran/mksine.
 *   - The ZIP MUST NOT introduce any composer dependency changes (require /
 *     require-dev diff); production servers have no composer access.
 *
 * Execution:
 *   Core updates MUTATE the very code that's running. We execute in-process
 *   but the sequence is carefully ordered so that every class we will need
 *   AFTER the swap has been pre-loaded (via early references in this method
 *   and in UpdateRunner), and any NEW class loaded after the swap will come
 *   from the NEW files (the autoloader path is unchanged; only its contents).
 *
 *   For maximum safety, prefer the CLI variant `php artisan mksine:update`,
 *   which runs in a fresh process that exits after the update.
 *
 * Pipeline:
 *   1. Lock.
 *   2. Extract to staging under base_path('packages/').
 *   3. Validate composer.json + config/mksine.php (name, version).
 *   4. Composer dependency-diff guard.
 *   5. Atomic swap: packages/mksine -> backup; staging -> packages/mksine.
 *   6. Publish: mksine-migrations, mksine-lang, mksine-fonts.
 *   7. migrate LAST.
 *   8. optimize:clear.
 */
final class CoreUpdater
{
    public const CORE_COMPOSER_NAME = 'miran/mksine';

    public function __construct(
        private readonly UpdateRunner $runner,
    ) {}

    public function update(string $zipPath, bool $force = false): UpdateResult
    {
        return $this->runner->run(
            UpdateTarget::Core,
            self::CORE_COMPOSER_NAME,
            function (UpdateLog $log, array &$steps, array &$warnings) use ($zipPath, $force): array {
                return $this->execute($zipPath, $force, $log, $steps, $warnings);
            }
        );
    }

    /**
     * @param  array<int,string>  $steps
     * @param  array<int,string>  $warnings
     * @return array{from: ?string, to: string, backup: ?string}
     */
    private function execute(
        string $zipPath,
        bool $force,
        UpdateLog $log,
        array &$steps,
        array &$warnings,
    ): array {
        $packagePath = base_path('packages/mksine');
        if (! is_dir($packagePath)) {
            throw UpdateException::validation(
                "Core update requires a path-repository install at packages/mksine. Directory not found: {$packagePath}"
            );
        }

        $this->assertMaxZipSize($zipPath);

        $fromVersion = (string) config('mksine.version', '0.0.0');
        $log->step('validate-zip', "zip={$zipPath}");
        $steps[] = 'validate-zip';

        $packagesDir = realpath(base_path('packages'));
        if ($packagesDir === false) {
            throw UpdateException::validation('base_path("packages") does not resolve.');
        }

        $stagingDir = $packagesDir.DIRECTORY_SEPARATOR.'.mks-staging-'.bin2hex(random_bytes(4)).'-core';

        $extractedRoot = ArchiveExtractor::extract($zipPath, $stagingDir);
        $log->step('extract', "root={$extractedRoot}");
        $steps[] = 'extract';

        try {
            $newComposerJson = $extractedRoot.DIRECTORY_SEPARATOR.'composer.json';
            if (! is_file($newComposerJson)) {
                throw UpdateException::validation('composer.json not found in ZIP.');
            }

            $composerData = json_decode((string) file_get_contents($newComposerJson), true);
            if (! is_array($composerData)) {
                throw UpdateException::validation('Invalid composer.json in ZIP.');
            }

            if (($composerData['name'] ?? null) !== self::CORE_COMPOSER_NAME) {
                throw UpdateException::validation(
                    'ZIP composer.json.name is "'.(string) ($composerData['name'] ?? '').'", expected "'.self::CORE_COMPOSER_NAME.'".'
                );
            }

            // Version: prefer config/mksine.php 'version' key (same source as Mksine::version()).
            $toVersion = $this->readConfigVersion($extractedRoot);
            if ($toVersion === null) {
                throw UpdateException::validation('Could not read version from config/mksine.php in ZIP.');
            }

            if (! $force) {
                $cmp = version_compare($toVersion, $fromVersion);
                if ($cmp === 0) {
                    throw UpdateException::validation("Core is already at version {$fromVersion}. Use --force on CLI to reinstall.");
                }
                if ($cmp < 0) {
                    throw UpdateException::validation("Downgrade rejected: {$fromVersion} -> {$toVersion}. Use --force on CLI to override.");
                }
            }

            // Dependency diff guard — production has no composer access.
            ComposerDependencyDiff::assertNoDependencyChanges(
                $packagePath.DIRECTORY_SEPARATOR.'composer.json',
                $newComposerJson
            );
            $log->step('dependency-diff', 'no changes');
            $steps[] = 'dependency-diff';

            $log->step('validate-manifest', "{$fromVersion} -> {$toVersion}");
            $steps[] = 'validate-manifest';

            // Pre-load classes we'll need AFTER the swap to avoid autoloading
            // from the new tree mid-replace. These class_exists() calls are
            // deliberate — they force Composer's autoloader to pull the class
            // definitions into the current process's class table.
            $this->preloadPostSwapClasses();

            // Swap.
            $backupManager = new BackupManager(UpdateTarget::Core, 'mksine-core');
            $backupManager->ensureRoot($packagePath);
            $backupPath = $backupManager->newBackupPath($packagePath, $fromVersion);

            $replacer = new AtomicReplacer;
            $replacer->swap($extractedRoot, $packagePath, $backupPath);
            $actualBackup = $replacer->backupPath();
            $this->cleanupStagingRemnants($stagingDir);

            $log->step('swap', "backup={$actualBackup}");
            $steps[] = 'swap';

            // Post-steps: publish first.
            $publishTags = ['mksine-migrations', 'mksine-lang', 'mksine-fonts'];
            foreach ($publishTags as $tag) {
                try {
                    Artisan::call('vendor:publish', ['--tag' => $tag, '--force' => true]);
                    $log->step('publish', $tag);
                    $steps[] = 'publish:'.$tag;
                } catch (\Throwable $e) {
                    $log->warning("publish {$tag} failed: ".$e->getMessage());
                    $warnings[] = "vendor:publish --tag={$tag} failed after swap.";
                }
            }

            // Optimize clear BEFORE migrate so service providers re-register with new code.
            try {
                Artisan::call('optimize:clear');
                $log->step('optimize-clear');
                $steps[] = 'optimize-clear';
            } catch (\Throwable $e) {
                $log->warning('optimize:clear failed: '.$e->getMessage());
                $warnings[] = 'optimize:clear failed after swap.';
            }

            // Migrate LAST.
            try {
                Artisan::call('migrate', ['--force' => true]);
                $output = trim(Artisan::output());
                if ($output !== '') {
                    $log->info('migrate output: '.str_replace(["\r", "\n"], [' ', ' '], $output));
                }
                $log->step('migrate');
                $steps[] = 'migrate';
            } catch (\Throwable $e) {
                $log->error('migrate failed: '.$e->getMessage());
                throw UpdateException::post(
                    "Core swapped to {$toVersion} but migrations failed. Manual recovery required. See log: ".$log->path(),
                    $e
                );
            }

            $keep = (int) config('mksine.updater.keep_backups', 3);
            $pruned = $backupManager->prune($packagePath, $keep);
            if ($pruned !== []) {
                $log->info('Pruned '.count($pruned).' old backup(s).');
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

    /**
     * Force-load every updater-subsystem class we may invoke AFTER the swap.
     *
     * PHP doesn't reload classes mid-request; once a class is in the table,
     * calling it is safe even if its source file was moved. New classes we
     * haven't referenced yet will autoload from the (now-new) source tree,
     * which is fine because the PSR-4 prefix still resolves to the same path.
     */
    private function preloadPostSwapClasses(): void
    {
        $classes = [
            Application::class,
            OptimizeClearCommand::class,
            MigrateCommand::class,
            VendorPublishCommand::class,
        ];
        foreach ($classes as $class) {
            class_exists($class);
        }
    }

    private function readConfigVersion(string $extractedRoot): ?string
    {
        $configFile = $extractedRoot.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'mksine.php';
        if (! is_file($configFile)) {
            return null;
        }

        $contents = (string) file_get_contents($configFile);
        if (preg_match("/'version'\s*=>\s*'([^']+)'/", $contents, $m) === 1) {
            return $m[1];
        }

        return null;
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
