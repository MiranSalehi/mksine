<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Plugins\PluginManager;
use Miran\Mksine\Core\Updater\UpdateException;
use Miran\Mksine\Core\Updater\Updaters\PluginUpdater;
use Miran\Mksine\Core\Updater\UpdateRunner;

/**
 * CLI entry point for project-plugin updates from a local ZIP file.
 *
 * Usage:
 *   php artisan mks-plugin:update {plugin} {file} [--force]
 *
 * Semantics mirror the Filament UI exactly; this command is the
 * preferred path for CI/CD and recovery scenarios.
 */
class UpdatePluginCommand extends Command
{
    protected $signature = 'mks-plugin:update
        {plugin : Plugin identifier (must match plugin.php id)}
        {file : Absolute or relative path to the update ZIP}
        {--force : Accept same-version or downgrade}';

    protected $description = 'Update an installed project plugin from a ZIP file.';

    public function handle(): int
    {
        if (! config('mksine.updater.enabled', true)) {
            $this->error('Updater is disabled via config(mksine.updater.enabled).');

            return self::FAILURE;
        }

        $pluginId = (string) $this->argument('plugin');
        $file = (string) $this->argument('file');
        $force = (bool) $this->option('force');

        $absolute = $this->resolveFilePath($file);
        if ($absolute === null) {
            $this->error("ZIP file not found: {$file}");

            return self::FAILURE;
        }

        $updater = new PluginUpdater(new UpdateRunner, app(PluginManager::class));

        $this->info("Updating plugin '{$pluginId}' from {$absolute}...");

        try {
            $result = $updater->update($pluginId, $absolute, $force);
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
                'Plugin %s updated: %s -> %s',
                $pluginId,
                $result->fromVersion ?? 'null',
                $result->toVersion ?? 'null'
            ));
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
