<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Updater\RollbackManager;

/**
 * Restore the most recent backup of the core miran/mksine package.
 *
 *   php artisan mksine:rollback
 *
 * Code-only rollback. DB migrations applied since the backup are NOT reversed.
 */
class RollbackCoreCommand extends Command
{
    protected $signature = 'mksine:rollback';

    protected $description = 'Roll back the core miran/mksine package to its most recent pre-update backup (CODE ONLY).';

    public function handle(): int
    {
        $this->warn('Rolling back core package. This restores CODE ONLY — migrations are NOT reversed.');

        if (! $this->confirm('Continue?', false)) {
            $this->line('Aborted.');

            return self::INVALID;
        }

        $result = (new RollbackManager)->rollbackCore();

        foreach ($result->steps as $step) {
            $this->line("  ✓ {$step}");
        }
        foreach ($result->warnings as $w) {
            $this->warn('  ! ' . $w);
        }

        if ($result->success) {
            $this->info(sprintf('Core rolled back: %s -> %s', $result->fromVersion ?? '?', $result->toVersion ?? '?'));
            $this->line('Log: ' . $result->logPath);

            return self::SUCCESS;
        }

        $this->error('Rollback failed: ' . $result->errorMessage);
        $this->line('Log: ' . $result->logPath);

        return self::FAILURE;
    }
}
