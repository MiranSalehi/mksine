<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Steps;

use Miran\Mksine\Core\Install\InstallContext;
use Miran\Mksine\Core\Install\StepResult;

class DiscoverHooksStep extends AbstractInstallStep
{
    public function key(): string
    {
        return 'hooks';
    }

    public function run(InstallContext $context): StepResult
    {
        if (! $this->hasTable('mks_hooks')) {
            return StepResult::skipped($this->message('database_not_ready'));
        }

        [$code, $output] = $this->artisan('mks:discover');

        return $code === 0
            ? StepResult::completed([$this->message('hooks_discovered')])
            : StepResult::failed($this->message('command_failed', ['command' => 'mks:discover']) . ' ' . $output);
    }
}
