<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Content;

use Miran\Mksine\Core\Events\Posts\PostCreated;
use Miran\Mksine\Core\Events\Posts\PostPublished;
use Miran\Mksine\Core\Events\Posts\PostUpdated;
use Miran\Mksine\Core\Hooks\HookManager;
use Miran\Mksine\Models\Post;

final class PostLifecycle
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function dispatchCreated(Post $post, array $context): void
    {
        $manager = app(HookManager::class);
        $data = $post->toArray();
        $manager->dispatch(new PostCreated($data, $context));

        if (self::isPublished($post)) {
            $manager->dispatch(new PostPublished($data, $context));
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function dispatchUpdated(Post $post, array $context, ?string $previousStatus): void
    {
        $manager = app(HookManager::class);
        $data = $post->toArray();
        $manager->dispatch(new PostUpdated($data, $context));

        if (self::isPublished($post) && $previousStatus !== 'published') {
            $manager->dispatch(new PostPublished($data, $context));
        }
    }

    private static function isPublished(Post $post): bool
    {
        return ($post->status ?? '') === 'published';
    }
}
