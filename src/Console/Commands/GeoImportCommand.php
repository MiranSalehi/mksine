<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Miran\Mksine\Jobs\Geo\RunGeoImportJob;
use Miran\Mksine\Services\Geo\GeoImportLogger;
use Miran\Mksine\Services\Geo\GeoImportOptions;
use Miran\Mksine\Services\Geo\GeoImportResult;
use Miran\Mksine\Services\Geo\GeoImportRunner;

class GeoImportCommand extends Command
{
    protected $signature = 'mks:geo:import
                            {--only= : Import only countries, states, or cities}
                            {--country= : Limit states/cities import to an ISO2 country code (runs without the queue)}
                            {--locations-database= : Read cities from this MySQL database instead of the dr5hn JSON files}
                            {--locations-table=csv-cities : Source table name inside --locations-database}
                            {--sync : Run the import synchronously instead of dispatching a queue job}';

    protected $description = 'Import global geo countries, states, and cities into geo_* tables';

    public function handle(GeoImportRunner $runner): int
    {
        if (! Schema::hasTable('geo_countries')) {
            $this->error('Geo tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $only = $this->option('only');
        $only = is_string($only) && $only !== '' ? strtolower($only) : null;
        $country = $this->option('country');
        $country = is_string($country) && $country !== '' ? strtoupper($country) : null;
        $locationsDatabase = $this->option('locations-database');
        $locationsDatabase = is_string($locationsDatabase) && $locationsDatabase !== '' ? $locationsDatabase : null;

        // One country takes seconds, so it never waits on a queue worker.
        $sync = (bool) $this->option('sync') || $country !== null;

        $options = new GeoImportOptions(
            only: $only,
            country: $country,
            locationsDatabase: $locationsDatabase,
            locationsTable: (string) $this->option('locations-table'),
            queueCityJobs: ! $sync,
        );

        if (! $options->isValidOnly()) {
            $this->error('Invalid --only value. Use countries, states, or cities.');

            return self::FAILURE;
        }

        $runId = Str::uuid()->toString();

        if ($sync) {
            return $this->runSync($runner, $runId, $options);
        }

        RunGeoImportJob::dispatch($runId, $options);

        $logPath = GeoImportLogger::logPath($runId);

        $this->info('Geo import dispatched to the queue.');
        $this->line('Run ID: ' . $runId);
        $this->line('Log: ' . $logPath);
        $this->line('Monitor: tail -f ' . $logPath);
        $this->line('Worker: ' . $this->workerCommand());
        $this->line('One country without the queue: php artisan mks:geo:import --sync --country=IR');

        return self::SUCCESS;
    }

    private function runSync(GeoImportRunner $runner, string $runId, GeoImportOptions $options): int
    {
        $logger = GeoImportLogger::open($runId);

        try {
            $result = $runner->run($runId, $options, $logger);
        } catch (\Throwable $exception) {
            $logger->error('Geo import failed: ' . $exception->getMessage());
            $this->error($exception->getMessage());
            $this->line('Log: ' . $logger->path());

            return self::FAILURE;
        }

        $this->renderSummary($options, $result);

        if ($result->hasFailures()) {
            $this->error('Geo import finished with problems: ' . $result->failureSummary() . '.');

            foreach ($result->failedCountries as $iso2 => $message) {
                $this->line("  {$iso2}: {$message}");
            }

            $this->line('Log: ' . $logger->path());

            return self::FAILURE;
        }

        if ($result->skippedCities > 0) {
            $this->warn('Geo import completed with ' . $result->skippedSummary() . '. The log lists the missing state ids.');
        } else {
            $this->info('Geo import completed.');
        }

        $this->line('Log: ' . $logger->path());

        return self::SUCCESS;
    }

    private function renderSummary(GeoImportOptions $options, GeoImportResult $result): void
    {
        $rows = [];

        if ($options->shouldImportCountries()) {
            $rows[] = ['Countries imported', $result->countries];
        }

        if ($options->shouldImportStates()) {
            $rows[] = ['States imported', $result->states];
        }

        if ($options->shouldImportCities()) {
            $rows[] = ['Cities imported', $result->cities];
            $rows[] = ['Cities skipped', $result->skippedCities];
            $rows[] = ['Countries failed', count($result->failedCountries)];
        }

        $this->table(['', 'Count'], $rows);
    }

    private function workerCommand(): string
    {
        $command = 'php artisan queue:work';

        $connection = config('mksine.geo_import.queue_connection');
        if (is_string($connection) && $connection !== '') {
            $command .= ' ' . $connection;
        }

        $queue = config('mksine.geo_import.queue_name');
        if (is_string($queue) && $queue !== '') {
            $command .= ' --queue=' . $queue;
        }

        return $command . ' --memory=1024';
    }
}
