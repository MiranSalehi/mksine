<?php

declare(strict_types=1);

namespace Miran\Mksine\Jobs\Geo;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Miran\Mksine\Services\Geo\GeoImportLogger;
use Miran\Mksine\Services\Geo\GeoImportOptions;
use Miran\Mksine\Services\Geo\GeoImportRunner;
use Throwable;

final class RunGeoImportJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(
        public readonly string $runId,
        public readonly GeoImportOptions $options,
    ) {
        $this->timeout = (int) config('mksine.geo_import.job_timeout', 3600);
        $this->configureQueue();
    }

    public function handle(GeoImportRunner $runner): void
    {
        $logger = GeoImportLogger::open($this->runId);

        try {
            $runner->run($this->runId, $this->options, $logger);
        } catch (Throwable $exception) {
            $logger->error('Geo import failed: '.$exception->getMessage());
            $this->fail($exception);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        GeoImportLogger::resume($this->runId)->error(
            'Geo import job failed: '.($exception?->getMessage() ?? 'unknown error'),
        );
    }

    private function configureQueue(): void
    {
        $connection = config('mksine.geo_import.queue_connection');
        $queue = config('mksine.geo_import.queue_name');

        if (is_string($connection) && $connection !== '') {
            $this->onConnection($connection);
        }

        if (is_string($queue) && $queue !== '') {
            $this->onQueue($queue);
        }
    }
}
