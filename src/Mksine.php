<?php

namespace Miran\Mksine;

use Illuminate\Support\Facades\Config;
use Miran\Mksine\Core\Install\InstallationState;
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
     * Whether the installer has finished on this site (or it predates the installer).
     */
    public function isInstalled(): bool
    {
        return app(InstallationState::class)->isInstalled();
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
