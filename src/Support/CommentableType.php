<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Miran\Mksine\Contracts\AllowsPublicComments;
use Miran\Mksine\Models\Post;

/**
 * Resolves which Eloquent classes may be the target of a public comment thread.
 *
 * {@see \Miran\Mksine\Livewire\Frontend\PostComments} used to accept any subclass of
 * {@see Model}. Combined with unlocked Livewire properties that meant a visitor could
 * retarget the form at {@see \App\Models\User} (or an order) and insert rows against it.
 * Registration in {@see config('mksine.commentable_types')} is the allowlist; implementing
 * {@see AllowsPublicComments} is the per-record gate. Both are required. Content types
 * from {@see \Miran\Mksine\Core\Content\ContentTypeRegistry} are not used: Post is reserved
 * out of that registry, and plugins such as ecom attach comments to models that are not CPTs.
 */
final class CommentableType
{
    /**
     * @return class-string<Model&AllowsPublicComments>|null
     */
    public static function resolve(string $type): ?string
    {
        $class = Relation::getMorphedModel($type) ?? $type;

        return in_array($class, self::allowed(), true) ? $class : null;
    }

    /**
     * @return list<class-string<Model&AllowsPublicComments>>
     */
    public static function allowed(): array
    {
        $classes = [];

        foreach ((array) config('mksine.commentable_types', [Post::class]) as $type) {
            if (! is_string($type) || $type === '') {
                continue;
            }

            $class = Relation::getMorphedModel($type) ?? $type;

            if (! class_exists($class) || ! is_a($class, Model::class, true) || ! is_a($class, AllowsPublicComments::class, true)) {
                continue;
            }

            $classes[] = $class;
        }

        return array_values(array_unique($classes));
    }

    public static function rateLimitKey(?string $ip = null): string
    {
        return 'mksine-comments:'.($ip ?: (request()->ip() ?: 'unknown'));
    }

    public static function maxPerMinute(): int
    {
        return max(1, (int) config('mksine.comments.max_per_minute', 5));
    }

    public static function decaySeconds(): int
    {
        return max(1, (int) config('mksine.comments.decay_seconds', 60));
    }
}
