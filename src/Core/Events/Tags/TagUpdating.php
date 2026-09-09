<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Events\Tags;

use Miran\Mksine\Core\Events\MksineEvent;

/**
 * Event fired before a tag is updated.
 * This is a BEFORE event, so it can be prevented.
 */
class TagUpdating extends MksineEvent
{
    /**
     * Get the event name.
     */
    public function name(): string
    {
        return 'tag.updating';
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
