<?php

declare(strict_types=1);

namespace Miran\Mksine\Services\Geo;

final readonly class GeoImportOptions
{
    public function __construct(
        public ?string $only = null,
        public ?string $country = null,
        public string $locationsDatabase = 'locations',
        public string $locationsTable = 'csv-cities',
        public bool $queueCityJobs = true,
    ) {}

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
