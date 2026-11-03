<?php

declare(strict_types=1);

namespace Miran\Mksine\Services\Geo;

final readonly class GeoImportOptions
{
    /**
     * @param  string|null  $locationsDatabase  MySQL database to read cities from; null reads the dr5hn per-country JSON files.
     */
    public function __construct(
        public ?string $only = null,
        public ?string $country = null,
        public ?string $locationsDatabase = null,
        public string $locationsTable = 'csv-cities',
        public bool $queueCityJobs = true,
    ) {}

    public function usesLocationsDatabase(): bool
    {
        return $this->locationsDatabase !== null;
    }

    public function shouldImportCountries(): bool
    {
        return $this->only === null || $this->only === 'countries';
    }

    public function shouldImportStates(): bool
    {
        return $this->only === null || $this->only === 'states';
    }

    public function shouldImportCities(): bool
    {
        return $this->only === null || $this->only === 'cities';
    }

    public function isValidOnly(): bool
    {
        return $this->only === null || in_array($this->only, ['countries', 'states', 'cities'], true);
    }
}
