<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Listeners\Posts;

use Illuminate\Support\Facades\Log;
use Miran\Mksine\Core\Events\MksineEvent;
use Miran\Mksine\Core\Hooks\MksineListenerInterface;

/**
 * Listener for 'post.updated' event.
 * This listener runs AFTER a post is updated.
 *
 * Example: Log post update, update cache, send notifications, etc.
 */
class PostUpdatedListener implements MksineListenerInterface
{
    /**
     * Handle the event - log post update.
     */
    public function handle(MksineEvent $event): void
    {
        $postId = $event->context()['post_id'] ?? null;
        $title = $event->data()->get('title');
        $userId = $event->context()['user_id'] ?? null;

        // Log all mutations that occurred during update
        $mutations = $event->mutations();

        // You can add more logic here:
        // - Update cache
        // - Send notification
        // - Trigger webhook
        // - etc.
    }

    /**
     * Should this listener handle the event?
     */
    public function shouldHandle(MksineEvent $event): bool
    {
        return true; // Always handle
    }

    /**
     * Should this listener be queued for async execution?
     */
    public function shouldQueue(): bool
    {
        return false; // Run immediately (queue system not implemented yet)
    }

    /**
     * Priority of this listener.
     */
    public function priority(): int
    {
        return 20; // Lower priority (run after critical listeners)
    }
}
