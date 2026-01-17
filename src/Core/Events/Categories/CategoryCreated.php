<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Events\Categories;

use Miran\Mksine\Core\Events\MksineEvent;

/**
 * Event fired after a category is created.
 * This is an AFTER event, so it cannot be prevented.
 */
class CategoryCreated extends MksineEvent
{
    /**
     * Get the event name.
     */
    public function name(): string
    {
        return 'category.created';
    }

    /**
     * This is an AFTER event, so it cannot be prevented.
     */
    public function canBePrevented(): bool
    {
        return false;
    }

    /**
     * Allow async execution for this event.
     */
    protected function allowAsync(): bool
    {
        return true;
    }
}
