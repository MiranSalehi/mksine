<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\PageCache;

use Illuminate\Database\Eloquent\Model;
use Miran\Mksine\Models\Category;
use Miran\Mksine\Models\Comment;
use Miran\Mksine\Models\Entry;
use Miran\Mksine\Models\Media;
use Miran\Mksine\Models\Menu;
use Miran\Mksine\Models\MenuItem;
use Miran\Mksine\Models\MenuLocation;
use Miran\Mksine\Models\MenuLocationAssignment;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\PageBuilderSection;
use Miran\Mksine\Models\Plugin;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Models\Setting;
use Miran\Mksine\Models\Tag;
use Miran\Mksine\Models\Theme;

/**
 * Flushes the page cache whenever content that public pages show is written.
 *
 * Plugins whose models appear on storefront pages (products, prices, stock) register them with
 * {@see watch()} so a change is visible at once instead of after the TTL.
 */
final class PageCacheInvalidator
{
    /**
     * Core models whose changes alter public pages.
     *
     * @var list<class-string<Model>>
     */
    public const array CORE_MODELS = [
        Post::class,
        Page::class,
        Category::class,
        Tag::class,
        Comment::class,
        Entry::class,
        Media::class,
        Menu::class,
        MenuItem::class,
        MenuLocation::class,
        MenuLocationAssignment::class,
        PageBuilderSection::class,
        Setting::class,
        Theme::class,
        Plugin::class,
    ];

    public static function registerCoreModels(): void
    {
        self::watch(...self::CORE_MODELS);
    }

    /**
     * Call once per application boot (a plugin's boot()); repeated flushes in one request are no-ops.
     *
     * @param  class-string<Model>  ...$models
     */
    public static function watch(string ...$models): void
    {
        foreach ($models as $model) {
            if (! class_exists($model)) {
                continue;
            }

            $flush = static fn () => app(PageCache::class)->flush();

            $model::saved($flush);
            $model::deleted($flush);

            if (method_exists($model, 'restored')) {
                $model::restored($flush);
            }
        }
    }
}
