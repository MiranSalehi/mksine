<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Miran\Mksine\Jobs\Geo\RunGeoImportJob;
use Miran\Mksine\Models\GeoCity;
use Miran\Mksine\Models\GeoCountry;
use Miran\Mksine\Models\GeoState;

uses(RefreshDatabase::class);

const GEO_IMPORT_DR5HN_BASE = 'https://raw.githubusercontent.com/dr5hn/countries-states-cities-database/master/contributions';

/**
 * @return array<string, mixed>
 */
function dr5hnCity(int $id, int $stateId, string $name, string $persianName, string $iso2 = 'IR', int $countryId = 103): array
{
    return [
        'id' => $id,
        'name' => $name,
        'native' => $name,
        'state_id' => $stateId,
        'country_id' => $countryId,
        'country_code' => $iso2,
        'type' => 'city',
        'latitude' => '35.69439000',
        'longitude' => '51.42151000',
        'timezone' => 'Asia/Tehran',
        'wikiDataId' => 'Q' . $id,
        'population' => 1000 + $id,
        'translations' => ['fa' => $persianName, 'de' => $name],
    ];
}

/**
 * dr5hn files keyed by path under contributions/; an int value is returned as that HTTP status.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function geoImportDataset(array $overrides = []): array
{
    return array_replace([
        'countries/countries.json' => [
            ['id' => 103, 'iso2' => 'IR', 'iso3' => 'IRN', 'name' => 'Iran', 'native' => 'ایران', 'translations' => ['fa' => 'ایران']],
            ['id' => 127, 'iso2' => 'LU', 'iso3' => 'LUX', 'name' => 'Luxembourg', 'native' => 'Lëtzebuerg', 'translations' => ['fa' => 'لوکزامبورگ']],
        ],
        'states/states.json' => [
            ['id' => 3929, 'country_id' => 103, 'country_code' => 'IR', 'iso2' => '23', 'name' => 'Tehran', 'native' => 'تهران', 'translations' => ['fa' => 'تهران']],
            ['id' => 3930, 'country_id' => 103, 'country_code' => 'IR', 'iso2' => '10', 'name' => 'Isfahan', 'native' => 'اصفهان', 'translations' => ['fa' => 'اصفهان']],
            ['id' => 5835, 'country_id' => 127, 'country_code' => 'LU', 'iso2' => 'LU', 'name' => 'Luxembourg', 'native' => 'Luxembourg', 'translations' => ['fa' => 'لوکزامبورگ']],
        ],
        'cities/IR.json' => [
            dr5hnCity(131, 3929, 'Tehran', 'تهران'),
            dr5hnCity(132, 3929, 'Shahriar', 'شهریار'),
            dr5hnCity(140, 3930, 'Isfahan', 'اصفهان'),
        ],
        'cities/LU.json' => [
            dr5hnCity(66724, 5835, 'Alzingen', 'آلزینگن', 'LU', 127),
        ],
    ], $overrides);
}

/**
 * Serve the dataset by reference, so a test can change it between two imports.
 *
 * @param  array<string, mixed>  $dataset
 */
function fakeDr5hn(array &$dataset): void
{
    Http::preventStrayRequests();
    Http::fake(function (Request $request) use (&$dataset) {
        $path = str_replace(GEO_IMPORT_DR5HN_BASE . '/', '', $request->url());
        $payload = $dataset[$path] ?? 404;

        return is_int($payload)
            ? Http::response('error', $payload)
            : Http::response($payload);
    });
}

function geoImportLogContents(): string
{
    return collect(File::glob(storage_path('logs/mksine-geo-import/*.log')))
        ->map(fn (string $path): string => (string) file_get_contents($path))
        ->implode("\n");
}

beforeEach(function (): void {
    $this->geoStoragePath = sys_get_temp_dir() . '/mksine-geo-import-test-' . uniqid();
    File::ensureDirectoryExists($this->geoStoragePath . '/logs');
    $this->app->useStoragePath($this->geoStoragePath);

    config([
        'mksine.geo_import.queue_connection' => null,
        'mksine.geo_import.queue_name' => null,
    ]);
});

afterEach(function (): void {
    File::deleteDirectory($this->geoStoragePath);
});

it('imports one country\'s cities from the dr5hn JSON file without a locations database or queue', function (): void {
    Bus::fake();
    $dataset = geoImportDataset();
    fakeDr5hn($dataset);

    $queries = [];
    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $this->artisan('mks:geo:import', ['--country' => 'IR'])
        ->expectsTable(['', 'Count'], [
            ['Countries imported', 2],
            ['States imported', 2],
            ['Cities imported', 3],
            ['Cities skipped', 0],
            ['Countries failed', 0],
        ])
        ->expectsOutputToContain('Geo import completed.')
        ->assertSuccessful();

    Bus::assertNothingDispatched();
    Bus::assertNothingBatched();
    Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), 'cities/LU.json'));
    expect(collect($queries)->filter(fn (string $sql): bool => str_contains($sql, 'csv-cities')))->toBeEmpty();

    expect(GeoCity::query()->orderBy('id')->pluck('id')->all())->toBe([131, 132, 140]);

    $tehran = GeoCity::query()->findOrFail(131);
    expect($tehran)
        ->geo_state_id->toBe(3929)
        ->geo_country_id->toBe(103)
        ->name->toBe('Tehran')
        ->native->toBe('تهران')
        ->type->toBe('city')
        ->timezone->toBe('Asia/Tehran')
        ->wiki_data_id->toBe('Q131')
        ->population->toBe(1131)
        ->latitude->toBe(35.69439)
        ->longitude->toBe(51.42151)
        ->translations->toEqual(['fa' => 'تهران', 'de' => 'Tehran']);
});

it('skips cities whose state is missing and imports the rest of the country', function (): void {
    $dataset = geoImportDataset();
    $dataset['cities/IR.json'][] = dr5hnCity(150, 999999, 'Nowhere', 'هیچ‌جا');
    fakeDr5hn($dataset);

    $this->artisan('mks:geo:import', ['--country' => 'IR'])
        ->expectsTable(['', 'Count'], [
            ['Countries imported', 2],
            ['States imported', 2],
            ['Cities imported', 3],
            ['Cities skipped', 1],
            ['Countries failed', 0],
        ])
        ->expectsOutputToContain('Geo import completed with 1 cities skipped (state missing in geo_states).')
        ->assertSuccessful();

    expect(GeoCity::query()->orderBy('id')->pluck('id')->all())->toBe([131, 132, 140])
        ->and(geoImportLogContents())
        ->toContain('Country IR — 1 cities skipped: state_id not in geo_states (999999)')
        ->toContain('Geo import completed — 1 cities skipped (state missing in geo_states)')
        ->not->toContain('finished with problems');
});

it('refuses to import cities for a country whose states are not imported', function (): void {
    $dataset = geoImportDataset();
    fakeDr5hn($dataset);

    $this->artisan('mks:geo:import', ['--only' => 'countries', '--sync' => true])->assertSuccessful();

    $this->artisan('mks:geo:import', ['--only' => 'cities', '--country' => 'IR'])
        ->expectsOutputToContain('No states for IR in geo_states, so its cities cannot be imported. Run `php artisan mks:geo:import --only=states --country=IR` first.')
        ->doesntExpectOutputToContain('completed')
        ->assertFailed();

    expect(GeoCity::query()->count())->toBe(0);
    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), '/cities/'));
});

it('refuses a global cities import while geo_states is empty', function (): void {
    $dataset = geoImportDataset();
    fakeDr5hn($dataset);

    $this->artisan('mks:geo:import', ['--only' => 'countries', '--sync' => true])->assertSuccessful();

    $this->artisan('mks:geo:import', ['--only' => 'cities', '--sync' => true])
        ->expectsOutputToContain('php artisan mks:geo:import --only=states')
        ->assertFailed();

    expect(GeoCity::query()->count())->toBe(0);
});

it('does not report completed when a country fails and still imports the other countries', function (): void {
    $dataset = geoImportDataset(['cities/LU.json' => 500]);
    fakeDr5hn($dataset);

    $this->artisan('mks:geo:import', ['--sync' => true])
        ->expectsTable(['', 'Count'], [
            ['Countries imported', 2],
            ['States imported', 3],
            ['Cities imported', 3],
            ['Cities skipped', 0],
            ['Countries failed', 1],
        ])
        ->expectsOutputToContain('1 countries failed (LU)')
        ->expectsOutputToContain('LU: Failed to download')
        ->doesntExpectOutputToContain('completed')
        ->assertFailed();

    expect(GeoCity::query()->where('geo_country_id', 103)->count())->toBe(3)
        ->and(GeoCity::query()->where('geo_country_id', 127)->count())->toBe(0)
        ->and(geoImportLogContents())
        ->toContain('Geo import finished with problems')
        ->not->toContain('Geo import completed');
});

it('imports cities from a country without a dr5hn city file as zero rows', function (): void {
    $dataset = geoImportDataset(['cities/LU.json' => 404]);
    fakeDr5hn($dataset);

    $this->artisan('mks:geo:import', ['--sync' => true])
        ->expectsOutputToContain('Geo import completed.')
        ->assertSuccessful();

    expect(GeoCity::query()->count())->toBe(3);
});

it('keeps ids stable and does not duplicate rows when the import runs again', function (): void {
    $dataset = geoImportDataset();
    fakeDr5hn($dataset);

    $this->artisan('mks:geo:import', ['--country' => 'IR'])->assertSuccessful();

    $countryIds = GeoCountry::query()->orderBy('id')->pluck('id')->all();
    $stateIds = GeoState::query()->orderBy('id')->pluck('id')->all();
    $cityIds = GeoCity::query()->orderBy('id')->pluck('id')->all();

    $dataset['cities/IR.json'][0]['name'] = 'Tehran City';

    $this->artisan('mks:geo:import', ['--country' => 'IR'])->assertSuccessful();

    expect(GeoCountry::query()->orderBy('id')->pluck('id')->all())->toBe($countryIds)
        ->and(GeoState::query()->orderBy('id')->pluck('id')->all())->toBe($stateIds)
        ->and(GeoCity::query()->orderBy('id')->pluck('id')->all())->toBe($cityIds)
        ->and($cityIds)->toBe([131, 132, 140])
        ->and(GeoCity::query()->findOrFail(131)->name)->toBe('Tehran City');
});

it('fails clearly when the given locations database cannot be read', function (): void {
    $dataset = geoImportDataset();
    fakeDr5hn($dataset);

    $this->artisan('mks:geo:import', ['--only' => 'countries', '--sync' => true])->assertSuccessful();
    $this->artisan('mks:geo:import', ['--only' => 'states', '--country' => 'IR'])->assertSuccessful();

    $this->artisan('mks:geo:import', [
        '--only' => 'cities',
        '--country' => 'IR',
        '--locations-database' => 'mksine_missing_locations',
    ])
        ->expectsOutputToContain('`mksine_missing_locations`.`csv-cities` is not readable')
        ->assertFailed();

    expect(GeoCity::query()->count())->toBe(0);
});

it('queues a global import and shows the worker and single-country commands', function (): void {
    Bus::fake();

    $this->artisan('mks:geo:import')
        ->expectsOutputToContain('Geo import dispatched to the queue.')
        ->expectsOutputToContain('php artisan queue:work --memory=1024')
        ->expectsOutputToContain('php artisan mks:geo:import --sync --country=IR')
        ->assertSuccessful();

    Bus::assertDispatched(RunGeoImportJob::class);
});

it('logs skipped cities from queued country jobs as a warning, not a failure', function (): void {
    config(['queue.default' => 'sync']);

    $dataset = geoImportDataset();
    $dataset['cities/IR.json'][] = dr5hnCity(150, 999999, 'Nowhere', 'هیچ‌جا');
    fakeDr5hn($dataset);

    $this->artisan('mks:geo:import')->assertSuccessful();

    expect(GeoCity::query()->count())->toBe(4)
        ->and(geoImportLogContents())
        ->toContain('2 country jobs queued')
        ->toContain('All queued country jobs finished — 0 of 2 failed, 1 cities skipped')
        ->toContain('Geo import completed — 1 cities skipped (state missing in geo_states)')
        ->not->toContain('finished with problems');
});
