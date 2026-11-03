<?php

declare(strict_types=1);

namespace Miran\Mksine\Services\Geo;

use RuntimeException;

/**
 * A geo import problem with a message an operator can act on.
 */
final class GeoImportException extends RuntimeException
{
    public static function downloadFailed(string $url, string $reason): self
    {
        return new self("Failed to download {$url}: {$reason}");
    }

    public static function datasetMissing(string $url): self
    {
        return new self("Dataset not found: {$url}");
    }

    public static function countryMissing(string $iso2): self
    {
        return new self(
            "Country {$iso2} is not in geo_countries. Run `php artisan mks:geo:import --only=countries` first.",
        );
    }

    public static function statesMissing(?string $iso2): self
    {
        if ($iso2 === null) {
            return new self(
                'geo_states is empty, so cities cannot be imported. Run `php artisan mks:geo:import --only=states` first.',
            );
        }

        return new self(
            "No states for {$iso2} in geo_states, so its cities cannot be imported. "
            . "Run `php artisan mks:geo:import --only=states --country={$iso2}` first.",
        );
    }

    public static function locationsDatabaseUnavailable(string $table, string $reason): self
    {
        return new self("Locations table {$table} is not readable: {$reason}");
    }
}
