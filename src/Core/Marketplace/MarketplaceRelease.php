<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Marketplace;

final class MarketplaceRelease
{
    public static function isNewer(string $catalogVersion, string $installedVersion): bool
    {
        if ($catalogVersion === '' || $installedVersion === '') {
            return false;
        }

        return version_compare($catalogVersion, $installedVersion) > 0;
    }
}
