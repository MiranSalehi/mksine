<?php

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Miran\Mksine\Support\ComposerBinary;
use Miran\Mksine\Support\ReleaseArchiveBuildRoots;
use Miran\Mksine\Support\ReleaseArchiveOptions;
use Miran\Mksine\Support\ReleaseArchiveStaging;
use Miran\Mksine\Support\ReleaseArchiveZipper;
use Miran\Mksine\Support\ReleaseAssetBuilder;
use Symfony\Component\Process\Process;

class ReleaseArchiveCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'mks:release-archive
                            {--output= : Absolute or project-relative path for the zip file}
                            {--core-only : Leave out plugins and themes (for updating an existing site)}
                            {--layout=single : single (document root = public/) or split (shared hosting: app folder + public_html)}
                            {--app-dir=mksine : Split layout: folder name for the application}
                            {--public-dir=public_html : Split layout: folder name for the public files}
                            {--no-dev : Install production Composer dependencies in a staging copy before zipping}
                            {--skip-build : Skip npm run build; only create the archive}
                            {--dry-run : List build roots and output path without building or zipping}';

    /**
     * @var string
     */
    protected $description = 'Build assets, clear bootstrap cache and zip the project for a fresh install (with plugins and themes) or, with --core-only, for an update';

    public function handle(): int
    {
        $basePath = base_path();

        try {
            $options = new ReleaseArchiveOptions(
                includeExtensions: ! $this->option('core-only'),
                layout: (string) $this->option('layout'),
                appDirectory: (string) $this->option('app-dir'),
                publicDirectory: (string) $this->option('public-dir'),
            );
        } catch (\InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $roots = ReleaseArchiveBuildRoots::discover($basePath, $options->includeExtensions);
        $outputPath = ReleaseArchiveZipper::resolveOutputPath($basePath, $this->option('output') ?: null);

        if ($this->option('dry-run')) {
            $this->displayDryRun($roots, $outputPath, $options);

            return self::SUCCESS;
        }

        if ($roots === [] && ! $this->option('skip-build')) {
            $this->error('No package.json files with a "build" script were found. Use --skip-build to only create the archive.');

            return self::FAILURE;
        }

        if (! $this->option('skip-build') && ! $this->buildRoots($basePath, $roots)) {
            return self::FAILURE;
        }

        $this->info('Clearing bootstrap cache…');
        ReleaseArchiveBuildRoots::clearBootstrapCache($basePath);

        $synced = ReleaseArchiveBuildRoots::syncPackageMigrationsToApp($basePath);
        if ($synced > 0) {
            $this->info("Synced {$synced} package migration(s) to database/migrations/.");
        }

        $archiveRoot = $basePath;
        $stagingRoot = null;

        if ($this->option('no-dev')) {
            $stagingRoot = $this->prepareProductionStaging($basePath, $options);

            if ($stagingRoot === null) {
                return self::FAILURE;
            }

            $archiveRoot = $stagingRoot;
        }

        $this->info('Creating archive…');

        try {
            ReleaseArchiveZipper::createArchive($archiveRoot, $outputPath, $options);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            if ($stagingRoot !== null) {
                File::deleteDirectory($stagingRoot);
            }
        }

        $this->info('Release archive created:');
        $this->line($outputPath);

        if ($options->isSplit()) {
            $this->line("Upload <comment>{$options->appDirectory}/</comment> next to <comment>{$options->publicDirectory}/</comment> (e.g. /home/user/{$options->appDirectory} and /home/user/{$options->publicDirectory}).");
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $roots
     */
    private function buildRoots(string $basePath, array $roots): bool
    {
        $error = ReleaseAssetBuilder::build($basePath, $roots, fn (string $line) => $this->output->write($line));

        if ($error !== null) {
            $this->error($error);

            return false;
        }

        return true;
    }

    /**
     * @param  list<string>  $command
     */
    private function runProcess(array $command, string $workingDirectory): bool
    {
        $process = new Process($command, $workingDirectory, null, null, 0);
        $process->run(function ($type, string $buffer): void {
            $this->output->write($buffer);
        });

        return $process->isSuccessful();
    }

    private function prepareProductionStaging(string $basePath, ReleaseArchiveOptions $options): ?string
    {
        $composer = ComposerBinary::argv($basePath);

        if ($composer === null) {
            $this->error('Composer was not found; --no-dev needs it to install production dependencies.');

            return null;
        }

        $stagingRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'mksine-release-staging-'.bin2hex(random_bytes(4));
        $this->info('Copying the project to a staging directory…');
        $copied = ReleaseArchiveStaging::copyProject($basePath, $stagingRoot, $options->includeExtensions);
        $this->line("  {$copied} file(s) copied to {$stagingRoot}");

        $this->info('Installing production Composer dependencies…');

        if (! $this->runProcess([...$composer, 'install', '--no-dev', '--optimize-autoloader', '--no-scripts', '--no-interaction', '--no-progress'], $stagingRoot)) {
            $this->error('composer install --no-dev failed in the staging directory.');
            File::deleteDirectory($stagingRoot);

            return null;
        }

        return $stagingRoot;
    }

    /**
     * @param  list<string>  $roots
     */
    private function displayDryRun(array $roots, string $outputPath, ReleaseArchiveOptions $options): void
    {
        $this->info('Build roots (in order):');
        if ($roots === []) {
            $this->line('  (none)');
            $this->warn('No package.json with a "build" script found.');
        }
        foreach ($roots as $root) {
            $this->line('  '.$root);
        }
        $this->newLine();
        $this->info('Archive output would be:');
        $this->line('  '.$outputPath);
        $this->newLine();
        $this->info('Archive:');
        $this->line('  Contents: '.($options->includeExtensions ? 'full (core, plugins, themes)' : 'core only (plugins and themes excluded)'));
        $this->line('  Layout: '.($options->isSplit() ? "split ({$options->appDirectory}/ + {$options->publicDirectory}/)" : 'single (document root = public/)'));
        $this->line('  Composer: '.($this->option('no-dev') ? 'production dependencies only (--no-dev)' : 'vendor/ as installed locally'));
        $this->newLine();
        $this->comment('Before zipping: syncs packages/mksine/database/migrations/*.php → database/migrations/ (mks_console_logs, mks_console_processes, …).');
        $this->newLine();
        $this->comment('Always excludes: any uploads/ tree, storage/logs, storage/framework/*, storage/app/* (keeps storage .gitignore scaffolds), node_modules, .git, .env* (keeps .env.example), bootstrap/cache/*.php, mksine-setup/, and public/* except allowlisted paths.');
    }
}
