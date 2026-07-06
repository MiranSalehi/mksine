<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Miran\Mksine\Jobs\Geo\RunGeoImportJob;
use Miran\Mksine\Services\Geo\GeoImportLogger;
use Miran\Mksine\Services\Geo\GeoImportOptions;
use Miran\Mksine\Services\Geo\GeoImportRunner;

class GeoImportCommand extends Command
{
    protected $signature = 'mks:geo:import
                            {--only= : Import only countries, states, or cities}
                            {--country= : Limit states/cities import to an ISO2 country code}
                            {--locations-database=locations : Source MySQL database for cities}
                            {--locations-table=csv-cities : Source table name inside the locations database}
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

        $options = new GeoImportOptions(
            only: $only,
            country: $country,
            locationsDatabase: (string) $this->option('locations-database'),
            locationsTable: (string) $this->option('locations-table'),
            queueCityJobs: ! (bool) $this->option('sync'),
        );

        if (! $options->isValidOnly()) {
            $this->error('Invalid --only value. Use countries, states, or cities.');

            return self::FAILURE;
        }

        $runId = Str::uuid()->toString();

        if ($this->option('sync')) {
            $logger = GeoImportLogger::open($runId);

            try {
                $runner->run($runId, $options, $logger);
            } catch (\Throwable $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            $this->info('Geo import completed.');
            $this->line('Log: '.$logger->path());

            return self::SUCCESS;
        }

        RunGeoImportJob::dispatch($runId, $options);

        $logPath = GeoImportLogger::logPath($runId);

        $this->info('Geo import dispatched to the queue.');
        $this->line('Run ID: '.$runId);
        $this->line('Log: '.$logPath);
        $this->line('Monitor: tail -f '.$logPath);
        $this->line('Worker: php artisan queue:work');

        return self::SUCCESS;
    }
}
