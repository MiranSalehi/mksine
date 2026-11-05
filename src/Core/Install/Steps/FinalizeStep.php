<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Steps;

use Miran\Mksine\Core\Install\InstallationState;
use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\InstallProgress;
use Miran\Mksine\Core\Install\StepResult;

/**
 * Writes the lock file and removes the progress file. Only runs once the database is migrated.
 */
class FinalizeStep extends AbstractInstallStep
{
    public function __construct(
        private readonly InstallationState $installation,
        private readonly InstallProgress $progress,
    ) {}

    public function key(): string
    {
        return 'finalize';
    }

    public function run(InstallContext $context): StepResult
    {
        if (! $context->migrate || ! $this->databaseIsReady()) {
            return StepResult::skipped($this->message('finalize_pending'));
        }

        $this->artisan('optimize:clear');

        $this->installation->markInstalled([
            'profile' => $context->profile->value,
            'theme' => $context->theme,
            'plugins' => $context->plugins,
            'locale' => $context->locale,
        ]);
        $this->progress->clear();

        return StepResult::completed([$this->message('finalized')]);
    }
}
