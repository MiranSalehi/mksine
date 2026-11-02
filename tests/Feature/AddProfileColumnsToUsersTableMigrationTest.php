<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Miran\Mksine\MksineServiceProvider;
use Miran\Mksine\Support\PackageVersion;

uses(RefreshDatabase::class);

const PROFILE_COLUMNS_MIGRATION = '2026_11_03_100000_add_profile_columns_to_users_table.php';

const PROFILE_COLUMNS = ['bio', 'date_of_birth', 'phone_country_code', 'phone_number'];

function profileColumnsMigrationPath(): string
{
    return dirname(__DIR__, 2) . '/database/migrations/' . PROFILE_COLUMNS_MIGRATION;
}

function profileColumnsMigration(): Migration
{
    return require profileColumnsMigrationPath();
}

/**
 * @param  list<string>  $columns
 */
function dropUsersColumnsIfPresent(array $columns): void
{
    foreach ($columns as $column) {
        if (Schema::hasColumn('users', $column)) {
            Schema::dropColumns('users', [$column]);
        }
    }
}

/**
 * @return array<string, array<string, mixed>>
 */
function usersColumnDefinitions(): array
{
    return collect(Schema::getColumns('users'))
        ->keyBy('name')
        ->only(PROFILE_COLUMNS)
        ->all();
}

/**
 * MySQL commits DDL implicitly, so RefreshDatabase cannot undo these tests: the row and the
 * columns would stay in the shared test database. Rebuild `users` the way the host defines it.
 */
afterEach(function (): void {
    DB::table('users')->delete();

    dropUsersColumnsIfPresent(PROFILE_COLUMNS);

    Schema::table('users', function (Blueprint $table): void {
        $table->text('bio')->nullable();
        $table->date('date_of_birth')->nullable();
        $table->string('phone_country_code', 5)->nullable();
        $table->string('phone_number', 20)->nullable();
    });
});

it('creates all four nullable columns when users has none of them', function (): void {
    dropUsersColumnsIfPresent(PROFILE_COLUMNS);
    expect(Schema::hasColumns('users', PROFILE_COLUMNS))->toBeFalse();

    profileColumnsMigration()->up();

    $columns = usersColumnDefinitions();

    expect(array_keys($columns))->toEqualCanonicalizing(PROFILE_COLUMNS)
        ->and(array_column($columns, 'nullable'))->each->toBeTrue()
        ->and($columns['bio']['type_name'])->toBe('text')
        ->and($columns['date_of_birth']['type_name'])->toBe('date')
        ->and($columns['phone_country_code']['type_name'])->toBe('varchar')
        ->and($columns['phone_number']['type_name'])->toBe('varchar');

    if (DB::getDriverName() === 'mysql') {
        expect($columns['phone_country_code']['type'])->toBe('varchar(8)')
            ->and($columns['phone_number']['type'])->toBe('varchar(32)');
    }
});

it('leaves existing columns and their data untouched', function (): void {
    $user = User::factory()->create(['phone_number' => '9123456789']);
    $definitionsBefore = usersColumnDefinitions();

    profileColumnsMigration()->up();
    profileColumnsMigration()->up();

    expect(DB::table('users')->where('id', $user->id)->value('phone_number'))->toBe('9123456789')
        ->and(usersColumnDefinitions())->toBe($definitionsBefore);
});

it('creates only the columns that are missing', function (): void {
    $user = User::factory()->create(['phone_country_code' => '+98']);
    $definitionsBefore = usersColumnDefinitions();
    dropUsersColumnsIfPresent(['bio', 'phone_number']);

    profileColumnsMigration()->up();

    $definitionsAfter = usersColumnDefinitions();

    expect(array_keys($definitionsAfter))->toEqualCanonicalizing(PROFILE_COLUMNS)
        ->and($definitionsAfter['date_of_birth'])->toBe($definitionsBefore['date_of_birth'])
        ->and($definitionsAfter['phone_country_code'])->toBe($definitionsBefore['phone_country_code'])
        ->and(DB::table('users')->where('id', $user->id)->value('phone_country_code'))->toBe('+98');

    if (DB::getDriverName() === 'mysql') {
        expect($definitionsAfter['phone_number']['type'])->toBe('varchar(32)');
    }
});

it('does not drop anything on rollback', function (): void {
    profileColumnsMigration()->down();

    expect(Schema::hasColumns('users', PROFILE_COLUMNS))->toBeTrue();
});

it('is published with the mksine-migrations tag', function (): void {
    $published = ServiceProvider::pathsToPublish(MksineServiceProvider::class, 'mksine-migrations');

    expect($published)->toHaveKey(realpath(profileColumnsMigrationPath()), database_path('migrations/' . PROFILE_COLUMNS_MIGRATION));
});

it('is in the path mksine:update migrates', function (): void {
    expect(base_path(PackageVersion::migrationsPathRelativeToBase() . '/' . PROFILE_COLUMNS_MIGRATION))->toBeFile();
});
