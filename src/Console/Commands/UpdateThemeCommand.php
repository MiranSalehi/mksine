<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Theme\ThemeManager as ThemeManagerService;
use Miran\Mksine\Core\Updater\UpdateException;
use Miran\Mksine\Core\Updater\Updaters\ThemeUpdater;
use Miran\Mksine\Core\Updater\UpdateRunner;

/**
 * CLI entry point for project-theme updates from a local ZIP file.
 *
 * Usage:
 *   php artisan mks:theme-update {theme} {file} [--force]
 */
class UpdateThemeCommand extends Command
{
    protected $signature = 'mks:theme-update
        {theme : Theme identifier (must match the target project theme directory)}
        {file : Absolute or relative path to the update ZIP}
        {--force : Accept same-version or downgrade}';

    protected $description = 'Update an installed project theme from a ZIP file.';

    public function handle(): int
    {
        if (! config('mksine.updater.enabled', true)) {
            $this->error('Updater is disabled via config(mksine.updater.enabled).');

            return self::FAILURE;
        }

        $themeId = (string) $this->argument('theme');
        $file = (string) $this->argument('file');
        $force = (bool) $this->option('force');

        $absolute = $this->resolveFilePath($file);
        if ($absolute === null) {
            $this->error("ZIP file not found: {$file}");

            return self::FAILURE;
        }

        $updater = new ThemeUpdater(new UpdateRunner, app(ThemeManagerService::class));

        $this->info("Updating theme '{$themeId}' from {$absolute}...");

        try {
            $result = $updater->update($themeId, $absolute, $force);
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
            $this->info(sprintf(
                'Theme %s updated: %s -> %s',
                $themeId,
                $result->fromVersion ?? 'null',
                $result->toVersion ?? 'null'
            ));
            $this->line('Log: ' . $result->logPath);

            return self::SUCCESS;
        }

        $this->error('Update failed (phase=' . $result->errorPhase . '): ' . $result->errorMessage);
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
