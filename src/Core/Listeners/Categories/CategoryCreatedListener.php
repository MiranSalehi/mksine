<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Listeners\Categories;

use Illuminate\Support\Facades\Log;
use Miran\Mksine\Core\Events\MksineEvent;
use Miran\Mksine\Core\Hooks\MksineListenerInterface;

/**
 * Listener for 'category.created' event.
 * This listener runs AFTER a category is created.
 *
 * Example: Log category creation, send notifications, etc.
 */
class CategoryCreatedListener implements MksineListenerInterface
{
    /**
     * Handle the event - log category creation.
     */
    public function handle(MksineEvent $event): void
    {
        $categoryId = $event->context()['category_id'] ?? null;
        $name = $event->data()->get('name');
        $userId = $event->context()['user_id'] ?? null;

        Log::info('Category created', [
            'category_id' => $categoryId,
            'name' => $name,
            'user_id' => $userId,
        ]);

        // You can add more logic here:
        // - Send notification
        // - Update cache
        // - Trigger webhook
        // etc.
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
