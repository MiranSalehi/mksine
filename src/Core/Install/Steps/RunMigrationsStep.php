<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Steps;

use Illuminate\Database\Migrations\Migrator;
use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\StepResult;

/**
 * Runs migrations. On shared hosting it runs one file at a time and stops after a time
 * budget, returning "incomplete" so the next request continues where this one stopped.
 */
class RunMigrationsStep extends AbstractInstallStep
{
    public const SHARED_TIME_BUDGET_SECONDS = 20.0;

    public function __construct(private readonly Migrator $migrator) {}

    public function key(): string
    {
        return 'migrate';
    }

    public function run(InstallContext $context): StepResult
    {
        if (! $context->migrate) {
            return StepResult::skipped($this->message('migrate_skipped'));
        }

        // Web requests have a time limit even on a VPS, so the browser installer always chunks.
        if (! $context->isShared() && app()->runningInConsole()) {
            [$code, $output] = $this->artisan('migrate', ['--force' => true]);

            return $code === 0
                ? StepResult::completed([$this->message('migrate_done')])
                : StepResult::failed($this->message('migrate_failed', ['output' => $output]));
        }

        return $this->runInChunks();
    }

    private function runInChunks(): StepResult
    {
        if (! $this->migrator->repositoryExists()) {
            $this->migrator->getRepository()->createRepository();
        }

        $pending = $this->pendingMigrations();
        $startedAt = microtime(true);
        $ranCount = 0;

        foreach ($pending as $path) {
            [$code, $output] = $this->artisan('migrate', ['--path' => $path, '--realpath' => true, '--force' => true]);

            if ($code !== 0) {
                return StepResult::failed($this->message('migrate_failed', ['output' => $output]));
            }

            $ranCount++;

            if ($ranCount < count($pending) && microtime(true) - $startedAt >= $this->timeBudget()) {
                return StepResult::incomplete([$this->message('migrate_partial', ['remaining' => count($pending) - $ranCount])]);
            }
        }

        return StepResult::completed([$this->message('migrate_done')]);
    }

    /**
     * @return array<string, string> Migration name => absolute path, in run order.
     */
    private function pendingMigrations(): array
    {
        $files = $this->migrator->getMigrationFiles([database_path('migrations'), ...$this->migrator->paths()]);

        return array_diff_key($files, array_flip($this->migrator->getRepository()->getRan()));
    }

    protected function timeBudget(): float
    {
        return self::SHARED_TIME_BUDGET_SECONDS;
    }
}
