<?php

declare(strict_types=1);

namespace Miran\Mksine\Services\Geo;

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Miran\Mksine\Jobs\Geo\ImportGeoCitiesCountryJob;
use Throwable;

final class GeoImportRunner
{
    /**
     * @throws GeoImportException when a phase cannot start (download failed, states missing, locations table unreadable)
     */
    public function run(string $runId, GeoImportOptions $options, GeoImportLogger $logger): GeoImportResult
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

        $result = new GeoImportResult;

        $logger->info('Geo import started' . ($options->country !== null ? " (country: {$options->country})" : ''));

        if ($options->shouldImportCountries()) {
            $logger->step('countries', 'Fetching dataset from dr5hn');
            $result->countries = $service->importCountries();
            $logger->step('countries', "{$result->countries} countries upserted");
        }

        if ($options->shouldImportStates()) {
            $logger->step('states', $options->country !== null
                ? "Fetching dataset for {$options->country}"
                : 'Fetching global states dataset from dr5hn');
            $result->states = $service->importStates($options->country);
            $logger->step('states', "{$result->states} states upserted");
        }

        if (! $options->shouldImportCities()) {
            $this->logOutcome($logger, $result);

            return $result;
        }

        $service->assertLocationsDatabaseAvailable();
        $service->assertStatesImported($options->country);

        $countryCodes = $service->countryCodesForImport($options->country);
        $totalCountries = count($countryCodes);
        $source = $options->usesLocationsDatabase()
            ? "locations table `{$options->locationsDatabase}`.`{$options->locationsTable}`"
            : 'dr5hn per-country JSON';

        $logger->step('cities', "Preparing import for {$totalCountries} countries from {$source}");

        if (! $options->queueCityJobs || $totalCountries === 1) {
            $this->importCitiesInline($service, $options, $logger, $countryCodes, $result);
            $this->logOutcome($logger, $result);

            return $result;
        }

        $this->dispatchCityImportBatch($runId, $options, $logger, $countryCodes);
        $result->citiesQueued = true;

        return $result;
    }

    /**
     * @param  list<string>  $countryCodes
     */
    private function importCitiesInline(
        GeoImportService $service,
        GeoImportOptions $options,
        GeoImportLogger $logger,
        array $countryCodes,
        GeoImportResult $result,
    ): void {
        $totalCountries = count($countryCodes);

        foreach ($countryCodes as $index => $countryCode) {
            $position = $index + 1;
            $logger->step('cities', "Country {$countryCode} ({$position}/{$totalCountries}) — loading rows");

            try {
                $counts = $service->importCitiesForCountryCode(
                    $countryCode,
                    $options->country !== null,
                    $result->cities,
                );
            } catch (Throwable $exception) {
                $logger->error("Country {$countryCode} failed: " . $exception->getMessage());
                $result->recordFailedCountry($countryCode, $exception->getMessage());

                continue;
            }

            $result->recordCountryCities($counts['imported'], $counts['skipped']);
            $logger->step(
                'cities',
                "Country {$countryCode} — {$counts['imported']} cities upserted, {$counts['skipped']} skipped (total: {$result->cities})",
            );
        }

        $logger->step('cities', "{$result->cities} cities upserted, {$result->skippedCities} skipped");
    }

    private function logOutcome(GeoImportLogger $logger, GeoImportResult $result): void
    {
        if ($result->hasFailures()) {
            $logger->error('Geo import finished with problems — ' . $result->failureSummary());

            return;
        }

        if ($result->skippedCities > 0) {
            $logger->warning('Geo import completed — ' . $result->skippedSummary());

            return;
        }

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
            ->name('mksine-geo-import-cities-' . $runId)
            ->allowFailures()
            ->finally(function (Batch $batch) use ($runId): void {
                $batchLogger = GeoImportLogger::resume($runId);
                $skipped = (int) Cache::pull(ImportGeoCitiesCountryJob::skippedCitiesCacheKey($runId), 0);

                $batchLogger->step(
                    'cities',
                    "All queued country jobs finished — {$batch->failedJobs} of {$batch->totalJobs} failed, {$skipped} cities skipped",
                );

                if ($batch->failedJobs > 0) {
                    $batchLogger->error(
                        "Geo import finished with problems — {$batch->failedJobs} failed country jobs, {$skipped} cities skipped",
                    );

                    return;
                }

                if ($skipped > 0) {
                    $batchLogger->warning("Geo import completed — {$skipped} cities skipped (state missing in geo_states)");

                    return;
                }

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
            $logger->error('Failed to dispatch city import batch: ' . $exception->getMessage());

            throw $exception;
        }

        $logger->step('cities', count($jobs) . ' country jobs queued — monitor with queue:work');
        $logger->info('Countries/states finished; cities import continues on the queue');
    }
}
