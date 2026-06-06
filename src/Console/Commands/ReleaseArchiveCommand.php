<?php

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Support\ReleaseArchiveBuildRoots;
use Miran\Mksine\Support\ReleaseArchiveZipper;
use Symfony\Component\Process\Process;

class ReleaseArchiveCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'mks:release-archive
                            {--output= : Absolute or project-relative path for the zip file}
                            {--skip-build : Skip npm run build; only create the archive}
                            {--dry-run : List build roots and output path without building or zipping}';

    /**
     * @var string
     */
    protected $description = 'Run npm run build for the mksine package and app root, clear bootstrap cache, then zip the core project (themes/plugins are excluded — install them separately on the server)';

    public function handle(): int
    {
        $basePath = base_path();
        $roots = ReleaseArchiveBuildRoots::discover($basePath);
        $outputPath = ReleaseArchiveZipper::resolveOutputPath($basePath, $this->option('output') ?: null);

        if ($this->option('dry-run')) {
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
            $this->comment('Before zipping: syncs packages/mksine/database/migrations/*.php → database/migrations/ (mks_console_logs, mks_console_processes, …).');
            $this->newLine();
            $this->comment('Excludes: plugins/, themes/, any uploads/ tree, storage/logs, storage/framework/*, storage/app/* (keeps storage .gitignore scaffolds), node_modules, .git, .env* (keeps .env.example), bootstrap/cache/*.php, and public/* except allowlisted paths.');

            return self::SUCCESS;
        }

        if ($roots === [] && ! $this->option('skip-build')) {
            $this->error('No package.json files with a "build" script were found. Use --skip-build to only create the archive.');

            return self::FAILURE;
        }

        if (! $this->option('skip-build')) {
            foreach ($roots as $root) {
                $label = str_replace($basePath.DIRECTORY_SEPARATOR, '', $root) ?: '(project root)';
                $this->info("Running npm run build in {$label}…");

                $process = new Process(['npm', 'run', 'build'], $root, null, null, 0);
                $process->run(function ($type, string $buffer): void {
                    $this->output->write($buffer);
                });

                if (! $process->isSuccessful()) {
                    $this->error('npm run build failed in '.$root);
                    $this->output->write($process->getErrorOutput());

                    return self::FAILURE;
                }
            }
        }

        $this->info('Clearing bootstrap cache…');
        ReleaseArchiveBuildRoots::clearBootstrapCache($basePath);

        $synced = ReleaseArchiveBuildRoots::syncPackageMigrationsToApp($basePath);
        if ($synced > 0) {
            $this->info("Synced {$synced} package migration(s) to database/migrations/.");
        }

        $this->info('Creating archive…');

        try {
            ReleaseArchiveZipper::createArchive($basePath, $outputPath);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Release archive created:');
        $this->line($outputPath);

        return self::SUCCESS;
    }
}
