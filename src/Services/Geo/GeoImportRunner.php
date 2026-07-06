<?php

declare(strict_types=1);

namespace Miran\Mksine\Services\Geo;

use Illuminate\Support\Facades\Bus;
use Miran\Mksine\Jobs\Geo\ImportGeoCitiesCountryJob;
use Throwable;

final class GeoImportRunner
{
    public function run(string $runId, GeoImportOptions $options, GeoImportLogger $logger): void
    {
        if (! $options->isValidOnly()) {
            throw new \InvalidArgumentException('Invalid --only value. Use countries, states, or cities.');
        }

        $memoryLimit = (string) config('mksine.geo_import.memory_limit', '512M');
        if ($options->shouldImportCities()) {
            ini_set('memory_limit', $memoryLimit);
        }

        $service = new GeoImportService(
            locationsDatabase: $options->locationsDatabase,
            locationsTable: $options->locationsTable,
            logger: $logger,
        );

        $logger->info('Geo import started'.($options->country !== null ? " (country: {$options->country})" : ''));

        if ($options->shouldImportCountries()) {
            $logger->step('countries', 'Fetching dataset from dr5hn');
            $count = $service->importCountries();
            $logger->step('countries', "Completed — {$count} countries upserted");
        }

        if ($options->shouldImportStates()) {
            $logger->step('states', $options->country !== null
                ? "Fetching dataset for {$options->country}"
                : 'Fetching global states dataset from dr5hn');
            $count = $service->importStates($options->country);
            $logger->step('states', "Completed — {$count} states upserted");
        }

        if (! $options->shouldImportCities()) {
            $logger->info('Geo import completed');

            return;
        }

        if (! $service->locationsDatabaseAvailable()) {
            $logger->warning('Locations database unavailable — cities import skipped');

            return;
        }

        $countryCodes = $service->countryCodesForImport($options->country);
        $totalCountries = count($countryCodes);

        if ($totalCountries === 0) {
            $logger->warning('No country codes found in locations database — cities import skipped');
            $logger->info('Geo import completed');

            return;
        }

        $logger->step('cities', "Preparing import for {$totalCountries} countries");

        if (! $options->queueCityJobs || $totalCountries === 1) {
            $this->importCitiesInline($service, $options, $logger, $countryCodes);

            return;
        }

        $this->dispatchCityImportBatch($runId, $options, $logger, $countryCodes);
    }

    /**
     * @param  list<string>  $countryCodes
     */
    private function importCitiesInline(
        GeoImportService $service,
        GeoImportOptions $options,
        GeoImportLogger $logger,
        array $countryCodes,
    ): void {
        $imported = 0;
        $totalCountries = count($countryCodes);

        foreach ($countryCodes as $index => $countryCode) {
            $position = $index + 1;
            $logger->step('cities', "Country {$countryCode} ({$position}/{$totalCountries}) — loading rows");

            $countryImported = $service->importCitiesForCountryCode(
                $countryCode,
                $options->country !== null,
                $imported,
            );

            $imported += $countryImported;
            $logger->step('cities', "Country {$countryCode} — {$countryImported} cities upserted (total: {$imported})");
        }

        $logger->step('cities', "Completed — {$imported} cities upserted");
        $logger->info('Geo import completed');
    }

    /**
     * @param  list<string>  $countryCodes
     */
    private function dispatchCityImportBatch(
        string $runId,
        GeoImportOptions $options,
        GeoImportLogger $logger,
        array $countryCodes,
    ): void {
        $jobs = [];
        foreach ($countryCodes as $index => $countryCode) {
            $jobs[] = new ImportGeoCitiesCountryJob(
                runId: $runId,
                countryIso2: $countryCode,
                explicitCountryFilter: $options->country !== null,
                countryIndex: $index + 1,
                countryTotal: count($countryCodes),
                locationsDatabase: $options->locationsDatabase,
                locationsTable: $options->locationsTable,
            );
        }

        $connection = config('mksine.geo_import.queue_connection');
        $queue = config('mksine.geo_import.queue_name');

        $batch = Bus::batch($jobs)
            ->name('mksine-geo-import-cities-'.$runId)
            ->allowFailures()
            ->finally(function () use ($runId): void {
                $batchLogger = GeoImportLogger::resume($runId);
                $batchLogger->step('cities', 'All queued country jobs finished');
                $batchLogger->info('Geo import completed');
            });

        if (is_string($connection) && $connection !== '') {
            $batch->onConnection($connection);
        }

        if (is_string($queue) && $queue !== '') {
            $batch->onQueue($queue);
        }

        try {
            $batch->dispatch();
        } catch (Throwable $exception) {
            $logger->error('Failed to dispatch city import batch: '.$exception->getMessage());

            throw $exception;
        }

        $logger->step('cities', count($jobs).' country jobs queued — monitor with queue:work');
        $logger->info('Countries/states finished; cities import continues on the queue');
    }
}
