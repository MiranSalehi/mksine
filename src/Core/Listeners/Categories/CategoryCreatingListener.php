<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Listeners\Categories;

use Miran\Mksine\Core\Events\MksineEvent;
use Miran\Mksine\Core\Hooks\MksineListenerInterface;

/**
 * Listener for 'category.creating' event.
 * This listener runs BEFORE a category is created.
 *
 * Example: Auto-generate slug from name.
 */
class CategoryCreatingListener implements MksineListenerInterface
{
    /**
     * Handle the event - generate slug from name.
     */
    public function handle(MksineEvent $event): void
    {
        $name = $event->data()->get('name');
        $slug = $event->data()->get('slug');

        // Generate slug if name exists and slug is empty
        if ($name && empty($slug)) {
            $generatedSlug = \Illuminate\Support\Str::slug($name);
            $event->updateData('slug', $generatedSlug);
        }
    }

    /**
     * Should this listener handle the event?
     */
    public function shouldHandle(MksineEvent $event): bool
    {
        // Only handle if name exists
        return ! empty($event->data()->get('name'));
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
