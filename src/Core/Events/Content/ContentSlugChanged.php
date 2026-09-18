<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Events\Content;

use Miran\Mksine\Core\Events\MksineEvent;

/**
 * Fired after a Post or Page slug is persisted to a new value.
 *
 * Data keys: type (post|page|CPT key), id, old, new, old_path, new_path.
 */
class ContentSlugChanged extends MksineEvent
{
    public const string NAME = 'mksine.content.slug_changed';

    public const string FILTER = 'mksine.content.slug_changed';

    public function name(): string
    {
        return self::NAME;
    }

    public function canBePrevented(): bool
    {
        return false;
    }
}
