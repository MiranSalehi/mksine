<?php

declare(strict_types=1);

namespace Miran\Mksine\Services\Geo;

/**
 * Counts for one geo import run, so callers can tell a full import from a partial one.
 */
final class GeoImportResult
{
    public int $countries = 0;

    public int $states = 0;

    public int $cities = 0;

    /**
     * Cities left out because their state_id is not in geo_states. A warning, not a failure:
     * the dr5hn data has a few such rows and the rest of the country is still imported.
     */
    public int $skippedCities = 0;

    /**
     * @var array<string, string> ISO2 => error message
     */
    public array $failedCountries = [];

    /**
     * True when the cities phase was handed to queued batch jobs and is not counted here.
     */
    public bool $citiesQueued = false;

    public function recordCountryCities(int $imported, int $skipped): void
    {
        $this->cities += $imported;
        $this->skippedCities += $skipped;
    }

    public function recordFailedCountry(string $iso2, string $message): void
    {
        $this->failedCountries[$iso2] = $message;
    }

    public function hasFailures(): bool
    {
        return $this->failedCountries !== [];
    }

    public function failureSummary(): string
    {
        return count($this->failedCountries) . ' countries failed (' . implode(', ', array_keys($this->failedCountries)) . ')';
    }

    public function skippedSummary(): string
    {
        return "{$this->skippedCities} cities skipped (state missing in geo_states)";
    }
}
