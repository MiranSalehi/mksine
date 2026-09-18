<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Events\Storefront;

use Miran\Mksine\Core\Events\MksineEvent;

/**
 * Fired after a storefront GET successfully resolves content (page, post, archive, home).
 *
 * Data keys: path, content_type, content_id, status.
 * Listeners must not write analytics tables in core; plugins consume this event.
 */
class StorefrontViewed extends MksineEvent
{
    public const string NAME = 'mksine.storefront.viewed';

    public const string FILTER = 'mksine.storefront.viewed';

    public function name(): string
    {
        return self::NAME;
    }

    public function canBePrevented(): bool
    {
        return false;
    }
}
