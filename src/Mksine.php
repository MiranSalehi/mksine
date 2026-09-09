<?php

namespace Miran\Mksine;

use Illuminate\Support\Facades\Config;
use Miran\Mksine\Support\PackageVersion;

class Mksine
{
    /**
     * Get the shipped package version (not a stale published config overlay).
     */
    public function version(): string
    {
        return PackageVersion::current();
    }

    /**
     * Check if a feature is enabled
     */
    public function isFeatureEnabled(string $feature): bool
    {
        $features = Config::get('mksine.features', []);

        return $features[$feature] ?? false;
    }

    /**
     * Get CMS configuration
     */
    public function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return Config::get('mksine', []);
        }

        return Config::get("mksine.{$key}", $default);
    }
}
