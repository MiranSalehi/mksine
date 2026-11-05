<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Miran\Mksine\Core\Install\Environment\EnvironmentProbe;
use Miran\Mksine\Core\Install\Environment\RequirementCheck;
use Miran\Mksine\Core\Install\Environment\RequirementsChecker;
use Miran\Mksine\Core\Install\InstallationState;
use Miran\Mksine\Support\Console\Health\SchedulerMonitor;

/**
 * Reports server requirements, environment capabilities, install state and cron health.
 */
class DoctorCommand extends Command
{
    protected $signature = 'mksine:doctor';

    protected $description = 'Check server requirements, environment capabilities, install state and the scheduler';

    public function handle(EnvironmentProbe $probe, InstallationState $installation): int
    {
        $checker = new RequirementsChecker(RequirementsChecker::writablePathsFor(base_path(), storage_path(), public_path()));

        $this->components->info('Requirements');
        foreach ($checker->check() as $check) {
            $this->components->twoColumnDetail($this->describe($check), $this->status($check));
        }

        $report = $probe->probe(public_path());

        $this->components->info('Environment');
        $this->components->twoColumnDetail('exec / proc_open', $this->yesNo($report->canExec) . ' / ' . $this->yesNo($report->canProcOpen));
        $this->components->twoColumnDetail('symlink', $this->yesNo($report->canSymlink));
        $this->components->twoColumnDetail('max_execution_time', $report->hasExecutionTimeLimit() ? $report->maxExecutionTime . 's' : 'unlimited');
        $this->components->twoColumnDetail('memory_limit', $report->memoryLimit);
        $this->components->twoColumnDetail('Public path', public_path());
        $this->components->twoColumnDetail('Recommended profile', $report->recommendedProfile()->label());

        $this->components->info('Installation');
        $metadata = $installation->metadata();
        $this->components->twoColumnDetail('Installed', $this->yesNo($installation->isInstalled()) . ($metadata === null ? '' : ' (' . ($metadata['installed_at'] ?? '') . ')'));
        $this->components->twoColumnDetail('Installation in progress', $this->yesNo($installation->isInstallationInProgress()));

        $heartbeat = SchedulerMonitor::lastHeartbeat();
        $this->components->twoColumnDetail('Scheduler last run', $heartbeat === null ? '<fg=yellow>never</>' : $heartbeat->diffForHumans());

        if (! $checker->passes()) {
            $this->components->error('Some required server features are missing.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function describe(RequirementCheck $check): string
    {
        return __('mksine::install.' . $check->labelKey, $check->parameters);
    }

    private function status(RequirementCheck $check): string
    {
        $current = $check->current === null ? '' : ' <fg=gray>' . $check->current . '</>';

        if ($check->passed) {
            return '<fg=green>OK</>' . $current;
        }

        return ($check->required ? '<fg=red>MISSING</>' : '<fg=yellow>RECOMMENDED</>') . $current;
    }

    private function yesNo(bool $value): string
    {
        return $value ? '<fg=green>yes</>' : '<fg=yellow>no</>';
    }
}
