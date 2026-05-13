<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Updater\UpdateException;
use Miran\Mksine\Core\Updater\Updaters\CoreUpdater;
use Miran\Mksine\Core\Updater\UpdateRunner;

/**
 * CLI entry point for the core miran/mksine package update.
 *
 * PREFERRED path for core updates: running from a fresh PHP process avoids
 * the mid-request class-table pitfalls inherent to replacing the code you
 * are currently executing.
 *
 * Usage:
 *   php artisan mksine:update {file} [--force]
 */
class UpdateCoreCommand extends Command
{
    protected $signature = 'mksine:update
        {file : Absolute or relative path to the update ZIP}
        {--force : Accept same-version or downgrade}';

    protected $description = 'Update the core miran/mksine package from a ZIP file (path-repository installs only).';

    public function handle(): int
    {
        if (! config('mksine.updater.enabled', true)) {
            $this->error('Updater is disabled via config(mksine.updater.enabled).');

            return self::FAILURE;
        }

        $file = (string) $this->argument('file');
        $force = (bool) $this->option('force');

        $absolute = $this->resolveFilePath($file);
        if ($absolute === null) {
            $this->error("ZIP file not found: {$file}");

            return self::FAILURE;
        }

        if (! $this->confirm('This will replace packages/mksine with the contents of ' . basename($absolute) . '. Continue?', false)) {
            $this->line('Aborted.');

            return self::INVALID;
        }

        $updater = new CoreUpdater(new UpdateRunner);

        $this->info('Updating core from ' . $absolute . '...');

        try {
            $result = $updater->update($absolute, $force);
        } catch (\Throwable $e) {
            $this->error('Unexpected failure: ' . $e->getMessage());

            return self::FAILURE;
        }

        foreach ($result->steps as $step) {
            $this->line("  ✓ {$step}");
        }
        foreach ($result->warnings as $warning) {
            $this->warn('  ! ' . $warning);
        }

        if ($result->success) {
            $this->info(sprintf('Core updated: %s -> %s', $result->fromVersion ?? 'null', $result->toVersion ?? 'null'));
            $this->line('Log: ' . $result->logPath);

            return self::SUCCESS;
        }

        $this->error('Update failed (phase=' . $result->errorPhase . '): ' . $result->errorMessage);
        if ($result->dbPossiblyDirty) {
            $this->error('DB may be partially migrated — manual inspection required.');
        }
        $this->line('Log: ' . $result->logPath);

        return $result->errorPhase === UpdateException::PHASE_POST
            ? self::FAILURE
            : self::INVALID;
    }

    private function resolveFilePath(string $file): ?string
    {
        if (is_file($file)) {
            return realpath($file) ?: $file;
        }

        $relative = base_path($file);
        if (is_file($relative)) {
            return realpath($relative) ?: $relative;
        }

        return null;
    }
}
