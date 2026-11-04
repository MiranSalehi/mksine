<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Console\Health;

use Carbon\CarbonImmutable;
use Cron\CronExpression;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Queue\Failed\CountableFailedJobProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Miran\Mksine\Models\ConsoleProcess;
use Miran\Mksine\Support\Console\AdminConsolePhpBinary;
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
     * Cache key for the task list read from the CLI scheduler.
     */
    public const SCHEDULE_CACHE_KEY = 'mksine:console:schedule-list';

    /**
     * @return list<array<string, mixed>>
     */
    private function scheduledTasks(): array
    {
        $runs = SchedulerMonitor::taskRuns();

        return array_map(function (array $task) use ($runs): array {
            $next = $this->nextRunDate($task['expression'], $task['timezone']);
            $run = $runs[$task['name']] ?? null;
            $lastRun = is_array($run) ? CarbonImmutable::createFromTimestamp((int) $run['finished_at']) : null;

            return [
                'name' => $task['name'],
                'expression' => $task['expression'],
                'next_run_at' => $next?->toIso8601String(),
                'next_run_human' => $next?->diffForHumans(),
                'last_run_human' => $lastRun?->diffForHumans(),
                'last_succeeded' => is_array($run) ? (bool) $run['succeeded'] : null,
            ];
        }, $this->scheduleDefinitions());
    }

    /**
     * Scheduled tasks as the CLI scheduler sees them.
     *
     * Plugins often register schedules only when `runningInConsole()`, so a web request cannot see
     * them; the list therefore comes from `schedule:list --json` (cached), with the in-process
     * schedule as a fallback when the CLI is unavailable.
     *
     * @return list<array{name: string, expression: string, timezone: string}>
     */
    private function scheduleDefinitions(): array
    {
        $listed = Cache::remember(self::SCHEDULE_CACHE_KEY, 600, fn (): ?array => $this->listScheduleViaCli());

        return is_array($listed) ? $listed : $this->listScheduleInProcess();
    }

    /**
     * @return list<array{name: string, expression: string, timezone: string}>|null
     */
    private function listScheduleViaCli(): ?array
    {
        try {
            $result = Process::path(base_path())
                ->timeout(20)
                ->run([AdminConsolePhpBinary::path(), 'artisan', 'schedule:list', '--json', '--no-ansi']);
        } catch (Throwable) {
            return null;
        }

        $decoded = $result->successful() ? json_decode(trim($result->output()), true) : null;

        if (! is_array($decoded)) {
            return null;
        }

        $tasks = [];

        foreach ($decoded as $task) {
            if (! is_array($task) || ! isset($task['expression'])) {
                continue;
            }

            $description = is_string($task['description'] ?? null) ? $task['description'] : '';

            $tasks[] = [
                'name' => $description !== '' ? $description : (string) ($task['command'] ?? ''),
                'expression' => (string) $task['expression'],
                'timezone' => (string) ($task['timezone'] ?? config('app.timezone', 'UTC')),
            ];
        }

        return $tasks;
    }

    /**
     * @return list<array{name: string, expression: string, timezone: string}>
     */
    private function listScheduleInProcess(): array
    {
        try {
            app(ConsoleKernel::class)->all();

            /** @var list<Event> $events */
            $events = app(Schedule::class)->events();
        } catch (Throwable) {
            return [];
        }

        return array_map(fn (Event $event): array => [
            'name' => SchedulerMonitor::taskKey($event),
            'expression' => $event->expression,
            'timezone' => (string) ($event->timezone ?: config('app.timezone', 'UTC')),
        ], $events);
    }

    private function nextRunDate(string $expression, string $timezone): ?CarbonImmutable
    {
        try {
            $next = (new CronExpression($expression))->getNextRunDate(now()->setTimezone($timezone));

            return CarbonImmutable::instance($next)->setTimezone((string) config('app.timezone', 'UTC'));
        } catch (Throwable) {
            return null;
        }
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
