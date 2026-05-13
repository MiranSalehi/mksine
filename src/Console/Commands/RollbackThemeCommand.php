<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Updater\RollbackManager;

/**
 * Restore the most recent backup for a project theme.
 *
 *   php artisan mks:theme-rollback {theme}
 */
class RollbackThemeCommand extends Command
{
    protected $signature = 'mks:theme-rollback {theme}';

    protected $description = 'Roll back a project theme to its most recent pre-update backup.';

    public function handle(): int
    {
        $themeId = (string) $this->argument('theme');

        $this->warn('Rolling back theme ' . $themeId . '.');

        if (! $this->confirm('Continue?', false)) {
            $this->line('Aborted.');

            return self::INVALID;
        }

        $result = (new RollbackManager)->rollbackTheme($themeId);

        foreach ($result->steps as $step) {
            $this->line("  ✓ {$step}");
        }
        foreach ($result->warnings as $w) {
            $this->warn('  ! ' . $w);
        }

        if ($result->success) {
            $this->info(sprintf('Theme %s rolled back: %s -> %s', $themeId, $result->fromVersion ?? '?', $result->toVersion ?? '?'));
            $this->line('Log: ' . $result->logPath);

            return self::SUCCESS;
        }

        $this->error('Rollback failed: ' . $result->errorMessage);
        $this->line('Log: ' . $result->logPath);

        return self::FAILURE;
    }
}
