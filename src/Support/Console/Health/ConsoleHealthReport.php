<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Console\Health;

use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Queue\Failed\CountableFailedJobProvider;
use Illuminate\Support\Facades\Queue;
use Miran\Mksine\Models\ConsoleProcess;
use Miran\Mksine\Support\Console\AdminConsoleProcessManager;
use Miran\Mksine\Support\Console\ConsoleProcessStatus;
use Throwable;

/**
 * Snapshot of scheduler, queue workers, queue backlog and console background processes
 * for the health panel of the admin console.
 */
final class ConsoleHealthReport
{
    public function __construct(
        private readonly AdminConsoleProcessManager $processes,
    ) {}

    /**
     * @return array{scheduler: array<string, mixed>, workers: array<string, mixed>, queue: array<string, mixed>, processes: list<array<string, mixed>>}
     */
    public function build(): array
    {
        $queue = $this->queue();

        return [
            'scheduler' => $this->scheduler(),
            'workers' => $this->workers($queue),
            'queue' => $queue,
            'processes' => $this->backgroundProcesses(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function scheduler(): array
    {
        $lastRun = SchedulerMonitor::lastHeartbeat();
        $age = $lastRun?->diffInSeconds(now(), true);

        $status = match (true) {
            $age === null => 'down',
            $age <= SchedulerMonitor::HEALTHY_WITHIN_SECONDS => 'healthy',
            $age <= SchedulerMonitor::LATE_WITHIN_SECONDS => 'late',
            default => 'down',
        };

        $tasks = $this->scheduledTasks();
        $nextTask = collect($tasks)->sortBy('next_run_at')->first();

        return [
            'status' => $status,
            'last_run_at' => $lastRun?->toIso8601String(),
            'last_run_human' => $lastRun?->diffForHumans(),
            'tasks' => $tasks,
            'next_task' => $nextTask,
            'cron_line' => sprintf('* * * * * cd %s && php artisan schedule:run >> /dev/null 2>&1', base_path()),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function scheduledTasks(): array
    {
        try {
            // Schedules declared in routes/console.php are registered when the console kernel loads its commands.
            app(ConsoleKernel::class)->all();

            /** @var list<Event> $events */
            $events = app(Schedule::class)->events();
        } catch (Throwable) {
            return [];
        }

        $runs = SchedulerMonitor::taskRuns();
        $tasks = [];

        foreach ($events as $event) {
            try {
                $next = CarbonImmutable::instance($event->nextRunDate());
            } catch (Throwable) {
                $next = null;
            }

            $run = $runs[$event->mutexName()] ?? null;
            $lastRun = is_array($run) ? CarbonImmutable::createFromTimestamp((int) $run['finished_at']) : null;

            $tasks[] = [
                'name' => $this->taskName($event),
                'expression' => $event->expression,
                'next_run_at' => $next?->toIso8601String(),
                'next_run_human' => $next?->diffForHumans(),
                'last_run_human' => $lastRun?->diffForHumans(),
                'last_succeeded' => is_array($run) ? (bool) $run['succeeded'] : null,
            ];
        }

        return $tasks;
    }

    private function taskName(Event $event): string
    {
        if ($event instanceof CallbackEvent) {
            return (string) ($event->description ?: 'Closure');
        }

        if (is_string($event->description) && $event->description !== '') {
            return $event->description;
        }

        $command = (string) $event->command;

        if (preg_match("/artisan'?\s+(.+)$/", $command, $matches) === 1) {
            return 'php artisan '.trim($matches[1]);
        }

        return $command;
    }

    /**
     * @param  array<string, mixed>  $queue
     * @return array<string, mixed>
     */
    private function workers(array $queue): array
    {
        if ($queue['sync']) {
            return ['status' => 'not_needed', 'items' => []];
        }

        $items = array_map(function (array $worker): array {
            return $worker + [
                'last_seen_human' => CarbonImmutable::createFromTimestamp($worker['last_seen'])->diffForHumans(),
                'uptime_human' => CarbonImmutable::createFromTimestamp($worker['started_at'])->diffForHumans(syntax: CarbonImmutable::DIFF_ABSOLUTE),
            ];
        }, QueueWorkerMonitor::activeWorkers());

        $status = match (true) {
            $items !== [] => 'healthy',
            ($queue['pending'] ?? 0) > 0 => 'down',
            default => 'idle',
        };

        return ['status' => $status, 'items' => $items];
    }

    /**
     * @return array<string, mixed>
     */
    private function queue(): array
    {
        $connectionName = (string) config('queue.default', 'sync');
        $driver = (string) config("queue.connections.{$connectionName}.driver", $connectionName);
        $queueName = (string) config("queue.connections.{$connectionName}.queue", 'default');

        $report = [
            'connection' => $connectionName,
            'queue' => $queueName,
            'driver' => $driver,
            'sync' => $driver === 'sync',
            'pending' => null,
            'delayed' => null,
            'failed' => $this->failedJobCount(),
            'oldest_pending_human' => null,
        ];

        if ($report['sync']) {
            return $report;
        }

        try {
            $connection = Queue::connection($connectionName);

            $report['pending'] = method_exists($connection, 'pendingSize')
                ? (int) $connection->pendingSize($queueName)
                : (int) $connection->size($queueName);

            if (method_exists($connection, 'delayedSize')) {
                $report['delayed'] = (int) $connection->delayedSize($queueName);
            }

            if (method_exists($connection, 'creationTimeOfOldestPendingJob')) {
                $oldest = $connection->creationTimeOfOldestPendingJob($queueName);

                if (is_numeric($oldest)) {
                    $report['oldest_pending_human'] = CarbonImmutable::createFromTimestamp((int) $oldest)->diffForHumans(syntax: CarbonImmutable::DIFF_ABSOLUTE);
                }
            }
        } catch (Throwable) {
            // Unreachable queue backend: leave the counters empty.
        }

        return $report;
    }

    private function failedJobCount(): ?int
    {
        try {
            $failer = app('queue.failer');

            return $failer instanceof CountableFailedJobProvider ? (int) $failer->count() : count($failer->all());
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Console-started processes that are still running (workers or scheduler started from the terminal).
     *
     * @return list<array<string, mixed>>
     */
    private function backgroundProcesses(): array
    {
        return ConsoleProcess::query()
            ->whereIn('status', [ConsoleProcessStatus::Pending, ConsoleProcessStatus::Running])
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (ConsoleProcess $process): ConsoleProcess => $this->processes->syncStatus($process))
            ->filter(fn (ConsoleProcess $process): bool => $process->status->isActive())
            ->map(fn (ConsoleProcess $process): array => $this->processes->present($process) + [
                'started_human' => $process->started_at?->diffForHumans(syntax: CarbonImmutable::DIFF_ABSOLUTE),
            ])
            ->values()
            ->all();
    }
}
