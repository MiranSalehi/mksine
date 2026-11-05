<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Contracts;

use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\StepResult;

/**
 * One idempotent unit of installation. Running a step twice must be safe, so a resumed
 * install can repeat the step that was interrupted.
 */
interface InstallStep
{
    /** Stable identifier, persisted in the install progress file. */
    public function key(): string;

    public function label(): string;

    public function run(InstallContext $context): StepResult;
}
