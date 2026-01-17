<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Listeners\Posts;

use Miran\Mksine\Core\Events\MksineEvent;
use Miran\Mksine\Core\Hooks\MksineListenerInterface;

/**
 * Listener for 'post.creating' event.
 * This listener runs BEFORE a post is created.
 *
 * Example: Auto-generate slug from title.
 */
class PostCreatingListener implements MksineListenerInterface
{
    /**
     * Handle the event - generate slug from title.
     */
    public function handle(MksineEvent $event): void
    {
        $title = $event->data()->get('title');
        $slug = $event->data()->get('slug');

        // Generate slug if title exists and slug is empty
        if ($title && empty($slug)) {
            $generatedSlug = \Illuminate\Support\Str::slug($title);
            $event->updateData('slug', $generatedSlug);
        }
    }

    /**
     * Should this listener handle the event?
     */
    public function shouldHandle(MksineEvent $event): bool
    {
        // Only handle if title exists
        return ! empty($event->data()->get('title'));
    }

    /**
     * Should this listener be queued for async execution?
     */
    public function shouldQueue(): bool
    {
        return false; // Run immediately (slug is needed before save)
    }

    /**
     * Priority of this listener.
     * Lower numbers run first.
     */
    public function priority(): int
    {
        return 10; // Medium priority
    }
}
