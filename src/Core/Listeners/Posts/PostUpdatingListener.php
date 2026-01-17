<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Listeners\Posts;

use Miran\Mksine\Core\Events\MksineEvent;
use Miran\Mksine\Core\Hooks\MksineListenerInterface;

/**
 * Listener for 'post.updating' event.
 * This listener runs BEFORE a post is updated.
 *
 * Example: Validate post data, prevent update if invalid.
 */
class PostUpdatingListener implements MksineListenerInterface
{
    /**
     * Handle the event - validate post update.
     */
    public function handle(MksineEvent $event): void
    {
        $content = $event->data()->get('content', '');

        // Example: Check for spam words
        $spamWords = ['spam', 'scam', 'fake'];

        foreach ($spamWords as $word) {
            if (stripos($content, $word) !== false) {
                $event->prevent("Post content contains prohibited word: {$word}");

                return;
            }
        }

        // You can add more validation logic here:
        // - Check permissions
        // - Validate slug uniqueness
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
