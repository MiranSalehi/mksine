<?php

declare(strict_types=1);

namespace Miran\Mksine\Jobs\Geo;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Miran\Mksine\Services\Geo\GeoImportLogger;
use Miran\Mksine\Services\Geo\GeoImportService;
use Throwable;

final class ImportGeoCitiesCountryJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(
        public readonly string $runId,
        public readonly string $countryIso2,
        public readonly bool $explicitCountryFilter,
        public readonly int $countryIndex,
        public readonly int $countryTotal,
        public readonly string $locationsDatabase = 'locations',
        public readonly string $locationsTable = 'csv-cities',
    ) {
        $this->timeout = (int) config('mksine.geo_import.job_timeout', 3600);
        $this->configureQueue();
    }

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        ini_set('memory_limit', (string) config('mksine.geo_import.memory_limit', '512M'));

        $logger = GeoImportLogger::resume($this->runId);
        $logger->step(
            'cities',
            "Country {$this->countryIso2} ({$this->countryIndex}/{$this->countryTotal}) — job started",
        );

        $service = new GeoImportService(
            locationsDatabase: $this->locationsDatabase,
            locationsTable: $this->locationsTable,
            logger: $logger,
        );

        try {
            $imported = $service->importCitiesForCountryCode(
                $this->countryIso2,
                $this->explicitCountryFilter,
            );

            $logger->step(
                'cities',
                "Country {$this->countryIso2} — {$imported} cities upserted",
            );
        } catch (Throwable $exception) {
            $logger->error("Country {$this->countryIso2} failed: ".$exception->getMessage());

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        GeoImportLogger::resume($this->runId)->error(
            "City import job for {$this->countryIso2} failed: ".($exception?->getMessage() ?? 'unknown error'),
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
