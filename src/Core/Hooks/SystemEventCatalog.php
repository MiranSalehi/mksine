<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

use Miran\Mksine\Core\Events\Content\ContentSlugChanged;
use Miran\Mksine\Core\Events\Posts\PostPublished;
use Miran\Mksine\Core\Events\Storefront\StorefrontNotFound;
use Miran\Mksine\Core\Events\Storefront\StorefrontViewed;

/**
 * Stable HookManager event names. Do not rename these strings.
 */
final class SystemEventCatalog
{
    public const string POST_CREATING = 'post.creating';

    public const string POST_CREATED = 'post.created';

    public const string POST_UPDATING = 'post.updating';

    public const string POST_UPDATED = 'post.updated';

    public const string POST_PUBLISHED = PostPublished::NAME;

    public const string STOREFRONT_NOT_FOUND = StorefrontNotFound::NAME;

    public const string STOREFRONT_VIEWED = StorefrontViewed::NAME;

    public const string CONTENT_SLUG_CHANGED = ContentSlugChanged::NAME;

    /**
     * @return array<string, string> event name => short description
     */
    public static function names(): array
    {
        return [
            self::POST_CREATING => 'Before a post is created (preventable).',
            self::POST_CREATED => 'After a post is created.',
            self::POST_UPDATING => 'Before a post is updated (preventable).',
            self::POST_UPDATED => 'After a post is updated.',
            self::POST_PUBLISHED => 'After a post becomes published.',
            self::STOREFRONT_NOT_FOUND => 'Storefront would return HTTP 404.',
            self::STOREFRONT_VIEWED => 'Storefront GET resolved content.',
            self::CONTENT_SLUG_CHANGED => 'Post or page slug changed.',
        ];
    }

    /**
     * After-events suitable as automation triggers (not preventable creating/updating).
     *
     * @return list<string>
     */
    public static function automationEventTriggers(): array
    {
        return [
            self::POST_CREATED,
            self::POST_UPDATED,
            self::POST_PUBLISHED,
            self::STOREFRONT_NOT_FOUND,
            self::STOREFRONT_VIEWED,
            self::CONTENT_SLUG_CHANGED,
        ];
    }
}
