<?php

declare(strict_types=1);

namespace Miran\Mksine\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Miran\Mksine\Services\Geo\GeoImportService;

class GeoImportCommand extends Command
{
    protected $signature = 'mks:geo:import
                            {--only= : Import only countries, states, or cities}
                            {--country= : Limit states/cities import to an ISO2 country code}
                            {--locations-database=locations : Source MySQL database for cities}
                            {--locations-table=csv-cities : Source table name inside the locations database}';

    protected $description = 'Import global geo countries, states, and cities into geo_* tables';

    public function handle(): int
    {
        if (! Schema::hasTable('geo_countries')) {
            $this->error('Geo tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $only = $this->option('only');
        $only = is_string($only) && $only !== '' ? strtolower($only) : null;
        $country = $this->option('country');
        $country = is_string($country) && $country !== '' ? strtoupper($country) : null;

        $service = new GeoImportService(
            locationsDatabase: (string) $this->option('locations-database'),
            locationsTable: (string) $this->option('locations-table'),
        );

        if ($only === null || $only === 'countries') {
            $this->info('Importing countries...');
            $count = $service->importCountries(function (int $imported): void {
                $this->output->write("\rCountries: {$imported}");
            });
            $this->newLine();
            $this->line("Countries imported: {$count}");
        }

        if ($only === null || $only === 'states') {
            $this->info('Importing states...');
            $count = $service->importStates($country, function (int $imported): void {
                $this->output->write("\rStates: {$imported}");
            });
            $this->newLine();
            $this->line("States imported: {$count}");
        }

        if ($only === null || $only === 'cities') {
            $this->info('Importing cities...');
            $count = $service->importCities($country, function (int $imported): void {
                $this->output->write("\rCities: {$imported}");
            });
            $this->newLine();
            $this->line("Cities imported: {$count}");
        }

        if ($only !== null && ! in_array($only, ['countries', 'states', 'cities'], true)) {
            $this->error('Invalid --only value. Use countries, states, or cities.');

            return self::FAILURE;
        }

        $this->info('Geo import completed.');

        return self::SUCCESS;
    }
}
