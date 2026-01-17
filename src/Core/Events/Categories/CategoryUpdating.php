<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Events\Categories;

use Miran\Mksine\Core\Events\MksineEvent;

/**
 * Event fired before a category is updated.
 * This is a BEFORE event, so it can be prevented.
 */
class CategoryUpdating extends MksineEvent
{
    /**
     * Get the event name.
     */
    public function name(): string
    {
        return 'category.updating';
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
