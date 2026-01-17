<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Listeners\Categories;

use Miran\Mksine\Core\Events\MksineEvent;
use Miran\Mksine\Core\Hooks\MksineListenerInterface;

/**
 * Listener for 'category.updating' event.
 * This listener runs BEFORE a category is updated.
 *
 * Example: Validate category data, prevent update if invalid.
 */
class CategoryUpdatingListener implements MksineListenerInterface
{
    /**
     * Handle the event - validate category update.
     */
    public function handle(MksineEvent $event): void
    {
        $description = $event->data()->get('description', '');

        // Example: Check for spam words
        $spamWords = ['spam', 'scam', 'fake'];

        foreach ($spamWords as $word) {
            if (stripos($description, $word) !== false) {
                $event->prevent("Category description contains prohibited word: {$word}");

                return;
            }
        }

        // You can add more validation logic here:
        // - Check permissions
        // - Validate slug uniqueness
        // - Validate parent category (prevent circular references)
        // - etc.
    }

    /**
     * Should this listener handle the event?
     */
    public function shouldHandle(MksineEvent $event): bool
    {
        return true; // Always validate
    }

    /**
     * Should this listener be queued for async execution?
     */
    public function shouldQueue(): bool
    {
        return false; // Run immediately (validation must happen before save)
    }

    /**
     * Priority of this listener.
     * Lower numbers run first - validation should run early.
     */
    public function priority(): int
    {
        return 5; // High priority (run early)
    }
}
