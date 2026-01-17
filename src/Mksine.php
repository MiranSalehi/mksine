<?php

namespace Miran\Mksine;

use Illuminate\Support\Facades\Config;

class Mksine
{
    /**
     * Get the configured CMS version
     */
    public function version(): string
    {
        return Config::get('mksine.version', '1.0.0');
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
