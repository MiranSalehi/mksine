<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Console\Health;

use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Per-process heartbeat for `queue:work` daemons (bound as a singleton, fed by queue events).
 *
 * Every worker writes its own entry into one shared cache map, throttled so idle workers
 * touch the cache about every {@see WRITE_INTERVAL_SECONDS} seconds.
 */
final class QueueWorkerMonitor
{
    public const CACHE_KEY = 'mksine:console:queue-workers';

    /** An idle worker that has not looped for this long is considered gone. */
    public const STALE_AFTER_SECONDS = 60;

    /** A worker busy with one job may stay silent this long before it is considered gone. */
    public const BUSY_GRACE_SECONDS = 1800;

    private const WRITE_INTERVAL_SECONDS = 10;

    private bool $isWorker = false;

    private string $connection = '';

    private string $queue = '';

    private int $startedAt = 0;

    private int $processed = 0;

    private ?string $currentJob = null;

    private float $lastWriteAt = 0.0;

    /** Whether the entry last written to the cache says "busy" (current_job set). */
    private bool $storedBusy = false;

    public function workerStarting(string $connection, string $queue): void
    {
        $this->becomeWorker($connection, $queue);
        $this->write(force: true);
    }

    public function looping(string $connection, string $queue): void
    {
        $this->becomeWorker($connection, $queue);
        $this->write();
    }

    public function jobProcessing(string $jobName): void
    {
        if (! $this->isWorker) {
            return;
        }

        $this->currentJob = $jobName;

        // Record the idle → busy transition right away so a long job does not look like a dead worker.
        // While the stored entry already says busy, bursts of short jobs need no extra writes.
        if (! $this->storedBusy) {
            $this->write(force: true);
        }
    }

    public function jobFinished(): void
    {
        if (! $this->isWorker) {
            return;
        }

        $this->processed++;
        $this->currentJob = null;
        $this->write();
    }

    public function workerStopping(): void
    {
        if (! $this->isWorker) {
            return;
        }

        $this->mutate(function (array $workers): array {
            unset($workers[$this->workerKey()]);

            return $workers;
        });
    }

    /**
     * Workers that reported recently (stale entries are dropped).
     *
     * @return list<array{key: string, host: string, pid: int, connection: string, queue: string, started_at: int, last_seen: int, processed: int, current_job: string|null, state: string}>
     */
    public static function activeWorkers(): array
    {
        try {
            $workers = Cache::get(self::CACHE_KEY, []);
        } catch (Throwable) {
            return [];
        }

        $now = now()->getTimestamp();
        $active = [];

        foreach (is_array($workers) ? $workers : [] as $key => $worker) {
            if (! is_array($worker)) {
                continue;
            }

            $silentFor = $now - (int) ($worker['last_seen'] ?? 0);
            $busy = ($worker['current_job'] ?? null) !== null;

            if ($silentFor > self::STALE_AFTER_SECONDS && ! ($busy && $silentFor <= self::BUSY_GRACE_SECONDS)) {
                continue;
            }

            $active[] = [
                'key' => (string) $key,
                'host' => (string) ($worker['host'] ?? ''),
                'pid' => (int) ($worker['pid'] ?? 0),
                'connection' => (string) ($worker['connection'] ?? ''),
                'queue' => (string) ($worker['queue'] ?? ''),
                'started_at' => (int) ($worker['started_at'] ?? 0),
                'last_seen' => (int) ($worker['last_seen'] ?? 0),
                'processed' => (int) ($worker['processed'] ?? 0),
                'current_job' => $busy ? (string) $worker['current_job'] : null,
                'state' => $busy ? 'busy' : 'idle',
            ];
        }

        usort($active, fn (array $a, array $b): int => $a['started_at'] <=> $b['started_at']);

        return $active;
    }

    private function becomeWorker(string $connection, string $queue): void
    {
        if (! $this->isWorker) {
            $this->isWorker = true;
            $this->startedAt = now()->getTimestamp();
        }

        $this->connection = $connection;
        $this->queue = $queue;
    }

    private function write(bool $force = false): void
    {
        if (! $this->isWorker) {
            return;
        }

        $now = microtime(true);

        if (! $force && ($now - $this->lastWriteAt) < self::WRITE_INTERVAL_SECONDS) {
            return;
        }

        $this->lastWriteAt = $now;
        $this->storedBusy = $this->currentJob !== null;

        $this->mutate(function (array $workers): array {
            $now = now()->getTimestamp();

            $workers = array_filter(
                $workers,
                fn (mixed $worker): bool => is_array($worker) && ($now - (int) ($worker['last_seen'] ?? 0)) <= self::BUSY_GRACE_SECONDS,
            );

            $workers[$this->workerKey()] = [
                'host' => (string) gethostname(),
                'pid' => (int) getmypid(),
                'connection' => $this->connection,
                'queue' => $this->queue,
                'started_at' => $this->startedAt,
                'last_seen' => $now,
                'processed' => $this->processed,
                'current_job' => $this->currentJob,
            ];

            return $workers;
        });
    }

    /**
     * @param  callable(array<string, mixed>): array<string, mixed>  $callback
     */
    private function mutate(callable $callback): void
    {
        $apply = function () use ($callback): void {
            $workers = Cache::get(self::CACHE_KEY, []);
            Cache::forever(self::CACHE_KEY, $callback(is_array($workers) ? $workers : []));
        };

        try {
            Cache::lock(self::CACHE_KEY.':lock', 5)->block(2, $apply);
        } catch (Throwable) {
            try {
                // Store without lock support, or the lock is contended: best effort.
                $apply();
            } catch (Throwable) {
                // Monitoring must never break a worker.
            }
        }
    }

    private function workerKey(): string
    {
        return gethostname().':'.getmypid();
    }
}
