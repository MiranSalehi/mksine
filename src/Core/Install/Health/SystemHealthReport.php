<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Install\Health;

use Carbon\CarbonImmutable;
use Miran\Mksine\Core\Install\Environment\EnvironmentProbe;
use Miran\Mksine\Core\Install\Environment\RequirementCheck;
use Miran\Mksine\Core\Install\Environment\RequirementsChecker;
use Miran\Mksine\Core\Install\InstallationState;
use Miran\Mksine\Core\Install\InstallProfile;
use Miran\Mksine\Core\Install\Steps\ConfigureRuntimeStep;
use Miran\Mksine\Support\Console\Health\ConsoleHealthReport;
use Miran\Mksine\Support\Console\Health\QueueWorkerMonitor;
use Miran\Mksine\Support\Console\Health\SchedulerMonitor;
use Miran\Mksine\Support\PackageVersion;
use Throwable;

/**
 * Everything a site owner needs to know is working after installation: install state,
 * cron, queue, server requirements, environment and risky settings.
 */
class SystemHealthReport
{
    public function __construct(
        private readonly InstallationState $installation,
        private readonly EnvironmentProbe $probe,
        private readonly ConsoleHealthReport $console,
    ) {}

    /**
     * @return array<string, list<HealthCheck>> Section key => checks.
     */
    public function build(?string $documentRoot = null): array
    {
        return [
            'installation' => $this->installation(),
            'scheduler' => $this->scheduler(),
            'queue' => $this->queue(),
            'requirements' => $this->requirements(),
            'environment' => $this->environment($documentRoot),
            'security' => $this->security($documentRoot),
        ];
    }

    /**
     * @param  array<string, list<HealthCheck>>  $sections
     */
    public static function problemCount(array $sections): int
    {
        return count(array_filter(array_merge(...array_values($sections)), fn (HealthCheck $check): bool => $check->isProblem()));
    }

    public function profile(): InstallProfile
    {
        $profile = $this->installation->metadata()['profile'] ?? null;

        return (is_string($profile) ? InstallProfile::tryFrom($profile) : null)
            ?? $this->probe->probe(public_path())->recommendedProfile();
    }

    /**
     * @return list<HealthCheck>
     */
    private function installation(): array
    {
        $metadata = $this->installation->metadata() ?? [];
        $installed = $this->installation->isInstalled();

        return [
            new HealthCheck(
                'installed',
                $this->label('installed'),
                $installed ? HealthCheck::OK : HealthCheck::ERROR,
                $installed ? $this->text(($metadata['legacy'] ?? false) ? 'installed_legacy' : 'yes') : $this->text('no')
            ),
            new HealthCheck('version', $this->label('version'), HealthCheck::INFO, PackageVersion::current()),
            new HealthCheck('installed_at', $this->label('installed_at'), HealthCheck::INFO, $this->formatDate($metadata['installed_at'] ?? null)),
            new HealthCheck('profile', $this->label('profile'), HealthCheck::INFO, $this->profile()->label()),
        ];
    }

    /**
     * @return list<HealthCheck>
     */
    private function scheduler(): array
    {
        $lastRun = SchedulerMonitor::lastHeartbeat();
        $status = ConsoleHealthReport::schedulerStatus($lastRun);

        return [
            new HealthCheck(
                'scheduler',
                $this->label('scheduler'),
                match ($status) {
                    'healthy' => HealthCheck::OK,
                    'late' => HealthCheck::WARNING,
                    default => HealthCheck::ERROR,
                },
                $lastRun?->diffForHumans() ?? $this->text('never'),
                $status === 'healthy' ? null : $this->text('scheduler_hint'),
                $status === 'healthy' ? null : ConfigureRuntimeStep::cronLine(),
            ),
        ];
    }

    /**
     * @return list<HealthCheck>
     */
    private function queue(): array
    {
        $queue = $this->console->queueSnapshot();
        $pending = $queue['pending'];
        $failed = $queue['failed'];

        $checks = [
            new HealthCheck(
                'queue_driver',
                $this->label('queue_driver'),
                $queue['sync'] ? HealthCheck::WARNING : HealthCheck::OK,
                (string) $queue['connection'],
                $queue['sync'] ? $this->text('queue_sync_hint') : null
            ),
        ];

        if ($queue['sync']) {
            return $checks;
        }

        $checks[] = new HealthCheck(
            'queue_pending',
            $this->label('queue_pending'),
            HealthCheck::INFO,
            $pending === null ? '—' : (string) $pending,
            $queue['oldest_pending_human'] ? $this->text('oldest', ['age' => $queue['oldest_pending_human']]) : null
        );

        $checks[] = new HealthCheck(
            'queue_failed',
            $this->label('queue_failed'),
            $failed === null ? HealthCheck::INFO : ($failed > 0 ? HealthCheck::WARNING : HealthCheck::OK),
            $failed === null ? '—' : (string) $failed,
            ($failed ?? 0) > 0 ? $this->text('queue_failed_hint') : null
        );

        $checks[] = $this->workerCheck((int) ($pending ?? 0));

        return $checks;
    }

    private function workerCheck(int $pending): HealthCheck
    {
        if ($this->profile() === InstallProfile::Shared) {
            $schedulerHealthy = ConsoleHealthReport::schedulerStatus(SchedulerMonitor::lastHeartbeat()) === 'healthy';

            return new HealthCheck(
                'queue_worker',
                $this->label('queue_worker'),
                $schedulerHealthy ? HealthCheck::OK : HealthCheck::ERROR,
                $this->text('worker_by_scheduler'),
                $schedulerHealthy ? null : $this->text('worker_needs_cron')
            );
        }

        $workers = count(QueueWorkerMonitor::activeWorkers());

        return new HealthCheck(
            'queue_worker',
            $this->label('queue_worker'),
            match (true) {
                $workers > 0 => HealthCheck::OK,
                $pending > 0 => HealthCheck::ERROR,
                default => HealthCheck::WARNING,
            },
            $workers > 0 ? $this->text('workers_running', ['count' => $workers]) : $this->text('workers_none'),
            $workers > 0 ? null : $this->text('worker_hint'),
        );
    }

    /**
     * Only failing requirements are listed; a single OK line stands for the rest.
     *
     * @return list<HealthCheck>
     */
    private function requirements(): array
    {
        $checker = new RequirementsChecker(RequirementsChecker::writablePathsFor(base_path(), storage_path(), public_path()));

        $problems = array_values(array_map(
            fn (RequirementCheck $check): HealthCheck => new HealthCheck(
                $check->key,
                __('mksine::install.' . $check->labelKey, $check->parameters),
                $check->required ? HealthCheck::ERROR : HealthCheck::WARNING,
                $check->current,
            ),
            array_filter($checker->check(), fn (RequirementCheck $check): bool => ! $check->passed),
        ));

        return $problems !== [] ? $problems : [
            new HealthCheck('requirements', $this->label('requirements'), HealthCheck::OK, $this->text('all_requirements_met')),
        ];
    }

    /**
     * @return list<HealthCheck>
     */
    private function environment(?string $documentRoot): array
    {
        $report = $this->probe->probe(public_path(), $documentRoot);
        $missingLinks = array_filter(array_keys((array) config('filesystems.links', [])), fn (string $link): bool => ! file_exists($link));

        return [
            new HealthCheck(
                'processes',
                $this->label('processes'),
                $report->canProcOpen ? HealthCheck::OK : HealthCheck::INFO,
                $report->canProcOpen ? $this->text('available') : $this->text('disabled'),
                $report->canProcOpen ? null : $this->text('processes_hint')
            ),
            new HealthCheck(
                'storage_link',
                $this->label('storage_link'),
                $missingLinks === [] ? HealthCheck::OK : HealthCheck::WARNING,
                $missingLinks === [] ? $this->text('yes') : $this->text('no'),
                $missingLinks === [] ? null : $this->text('storage_link_hint')
            ),
            new HealthCheck(
                'time_limit',
                $this->label('time_limit'),
                HealthCheck::INFO,
                $report->hasExecutionTimeLimit() ? $report->maxExecutionTime . 's' : $this->text('unlimited')
            ),
            new HealthCheck('memory_limit', $this->label('memory_limit'), HealthCheck::INFO, $report->memoryLimit),
            new HealthCheck('public_path', $this->label('public_path'), HealthCheck::INFO, public_path()),
            new HealthCheck('php', $this->label('php'), HealthCheck::INFO, PHP_VERSION),
        ];
    }

    /**
     * @return list<HealthCheck>
     */
    private function security(?string $documentRoot): array
    {
        $production = app()->environment('production');
        $debug = (bool) config('app.debug');

        $checks = [
            new HealthCheck(
                'debug',
                $this->label('debug'),
                $debug && $production ? HealthCheck::ERROR : ($debug ? HealthCheck::WARNING : HealthCheck::OK),
                $debug ? $this->text('on') : $this->text('off'),
                $debug ? $this->text('debug_hint') : null
            ),
            new HealthCheck(
                'environment',
                $this->label('environment'),
                $production ? HealthCheck::OK : HealthCheck::WARNING,
                (string) app()->environment(),
                $production ? null : $this->text('environment_hint')
            ),
        ];

        if (EnvironmentProbe::projectIsInsideWebRoot(base_path(), public_path(), $documentRoot)) {
            $checks[] = new HealthCheck('web_root', $this->label('web_root'), HealthCheck::ERROR, $this->text('inside_web_root'), $this->text('inside_web_root_hint'));
        }

        return $checks;
    }

    private function formatDate(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '—';
        }

        try {
            $date = CarbonImmutable::parse($value)->setTimezone((string) config('app.timezone', 'UTC'));
        } catch (Throwable) {
            return $value;
        }

        return $date->format('Y-m-d H:i') . ' (' . $date->diffForHumans() . ')';
    }

    private function label(string $key): string
    {
        return __('mksine::system_health.checks.' . $key);
    }

    /**
     * @param  array<string, mixed>  $replace
     */
    private function text(string $key, array $replace = []): string
    {
        return __('mksine::system_health.values.' . $key, $replace);
    }
}
