<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Console\Health;

use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Records when `schedule:run` last started and how each scheduled task last finished,
 * so the admin console can tell whether cron (or `schedule:work`) is actually running.
 */
final class SchedulerMonitor
{
    public const HEARTBEAT_KEY = 'mksine:console:scheduler-heartbeat';

    public const TASK_RUNS_KEY = 'mksine:console:scheduled-task-runs';

    /** Heartbeat younger than this means the scheduler is healthy (cron fires every minute). */
    public const HEALTHY_WITHIN_SECONDS = 120;

    /** Heartbeat younger than this (but older than healthy) means the scheduler is late. */
    public const LATE_WITHIN_SECONDS = 600;

    public static function recordHeartbeat(): void
    {
        try {
            Cache::forever(self::HEARTBEAT_KEY, now()->getTimestamp());
        } catch (Throwable) {
            // Monitoring must never break the scheduler.
        }
    }

    public static function lastHeartbeat(): ?CarbonImmutable
    {
        try {
            $timestamp = Cache::get(self::HEARTBEAT_KEY);
        } catch (Throwable) {
            return null;
        }

        return is_numeric($timestamp) ? CarbonImmutable::createFromTimestamp((int) $timestamp) : null;
    }

    public static function recordTaskRun(Event $task, bool $succeeded, ?float $runtimeSeconds = null): void
    {
        try {
            $runs = Cache::get(self::TASK_RUNS_KEY, []);
            $runs = is_array($runs) ? $runs : [];

            $key = self::taskKey($task);

            $runs[$key] = [
                'finished_at' => now()->getTimestamp(),
                'succeeded' => $succeeded,
                'runtime' => $runtimeSeconds ?? ($runs[$key]['runtime'] ?? null),
            ];

            Cache::forever(self::TASK_RUNS_KEY, $runs);
        } catch (Throwable) {
            // Monitoring must never break the scheduler.
        }
    }

    /**
     * Stable task name, identical to what `schedule:list --json` reports (description, else the command).
     */
    public static function taskKey(Event $task): string
    {
        if (is_string($task->description) && $task->description !== '') {
            return $task->description;
        }

        if ($task instanceof CallbackEvent) {
            return (string) $task->getSummaryForDisplay();
        }

        return $task->normalizeCommand((string) $task->command);
    }

    /**
     * @return array<string, array{finished_at: int, succeeded: bool, runtime: float|null}>
     */
    public static function taskRuns(): array
    {
        try {
            $runs = Cache::get(self::TASK_RUNS_KEY, []);
        } catch (Throwable) {
            return [];
        }

        return is_array($runs) ? $runs : [];
    }
}
