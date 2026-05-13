<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Updater\RollbackManager;

/**
 * Restore the most recent backup for a project plugin.
 *
 *   php artisan mks-plugin:rollback {plugin}
 *
 * Code-only rollback. DB migrations applied since the backup are NOT
 * reversed — operators must restore the database separately if needed.
 */
class RollbackPluginCommand extends Command
{
    protected $signature = 'mks-plugin:rollback {plugin}';

    protected $description = 'Roll back a project plugin to its most recent pre-update backup (CODE ONLY; DB not reverted).';

    public function handle(): int
    {
        $pluginId = (string) $this->argument('plugin');

        $this->warn('Rolling back plugin ' . $pluginId . '. This restores CODE ONLY — migrations are NOT reversed.');

        if (! $this->confirm('Continue?', false)) {
            $this->line('Aborted.');

            return self::INVALID;
        }

        $result = (new RollbackManager)->rollbackPlugin($pluginId);

        foreach ($result->steps as $step) {
            $this->line("  ✓ {$step}");
        }
        foreach ($result->warnings as $w) {
            $this->warn('  ! ' . $w);
        }

        if ($result->success) {
            $this->info(sprintf('Plugin %s rolled back: %s -> %s', $pluginId, $result->fromVersion ?? '?', $result->toVersion ?? '?'));
            $this->line('Log: ' . $result->logPath);

            return self::SUCCESS;
        }

        $this->error('Rollback failed: ' . $result->errorMessage);
        $this->line('Log: ' . $result->logPath);

        return self::FAILURE;
    }
}
