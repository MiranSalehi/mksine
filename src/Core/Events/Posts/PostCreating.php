<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Events\Posts;

use Miran\Mksine\Core\Events\MksineEvent;

/**
 * Event fired before a post is created.
 * This is a BEFORE event, so it can be prevented.
 */
class PostCreating extends MksineEvent
{
    /**
     * Get the event name.
     */
    public function name(): string
    {
        return 'post.creating';
    }

    /**
     * This is a BEFORE event, so it can be prevented.
     */
    public function canBePrevented(): bool
    {
        return true;
    }

    /**
     * Allow async execution for this event.
     */
    protected function allowAsync(): bool
    {
        return false;
    }
}
