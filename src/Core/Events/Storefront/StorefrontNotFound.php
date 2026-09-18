<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Events\Storefront;

use Miran\Mksine\Core\Events\MksineEvent;

/**
 * Fired when a storefront GET/HEAD request would render HTTP 404.
 * Listeners must not perform redirects; use the {@see \Miran\Mksine\Core\Hooks\Hooks::filter}
 * named {@see self::FILTER} instead.
 */
class StorefrontNotFound extends MksineEvent
{
    public const string NAME = 'mksine.storefront.not_found';

    public const string FILTER = 'mksine.storefront.not_found';

    public function name(): string
    {
        return self::NAME;
    }

    public function canBePrevented(): bool
    {
        return false;
    }
}
