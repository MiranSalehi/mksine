<?php

namespace Miran\Mksine\Core\Hooks;

use Miran\Mksine\Models\MenuLocation;

class MenuLocationManager
{
    protected array $locations = [];

    /**
     * Register a new menu location.
     */
    public function registerLocation(string $key, string $label): void
    {
        $this->locations[$key] = $label;
    }

    /**
     * Register multiple menu locations.
     */
    public function registerLocations(array $locations): void
    {
        foreach ($locations as $key => $label) {
            $this->registerLocation($key, $label);
        }
    }

    /**
     * Get all registered locations.
     */
    public function getLocations(): array
    {
        return $this->locations;
    }

    /**
     * Get a specific location label.
     */
    public function getLocationLabel(string $key): ?string
    {
        return $this->locations[$key] ?? null;
    }

    /**
     * Sync registered locations to the database.
     * This ensures all programmatically registered locations exist in the DB.
     * It does NOT remove locations from DB that are not in code (to preserve user-created ones if any).
     */
    public function syncToDatabase(): void
    {
        foreach ($this->locations as $key => $label) {
            MenuLocation::firstOrCreate(
                ['key' => $key],
                ['label' => $label]
            );
        }
    }
}
