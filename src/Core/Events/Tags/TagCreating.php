<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Events\Tags;

use Miran\Mksine\Core\Events\MksineEvent;

/**
 * Event fired before a tag is created.
 * This is a BEFORE event, so it can be prevented.
 */
class TagCreating extends MksineEvent
{
    /**
     * Get the event name.
     */
    public function name(): string
    {
        return 'tag.creating';
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
