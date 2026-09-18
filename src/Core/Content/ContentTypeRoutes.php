<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Content;

use Illuminate\Support\Facades\Route;
use Miran\Mksine\Core\Permalink;
use Miran\Mksine\Http\Middleware\EnsureActiveThemeDependencies;
use Miran\Mksine\Livewire\Frontend\FrontendResolver;

/**
 * Registers storefront archive/single routes after plugins boot into {@see ContentTypeRegistry}.
 */
final class ContentTypeRoutes
{
    public static function register(): void
    {
        $registry = app(ContentTypeRegistry::class);
        $blocked = self::blockedUris();

        Route::middleware(['web', EnsureActiveThemeDependencies::class])->group(function () use ($registry, $blocked): void {
            foreach ($registry->publicTypes() as $type) {
                $indexName = 'content.'.$type->key.'.index';
                $showName = 'content.'.$type->key.'.show';

                if ($type->hasArchive && ! Route::has($indexName)) {
                    $archive = $type->archivePath();
                    if (! in_array($archive, $blocked, true)) {
                        Route::get($archive, FrontendResolver::class)
                            ->defaults('page', 'entry-list')
                            ->defaults('contentType', $type->key)
                            ->name($indexName);
                    }
                }

                if (! Route::has($showName)) {
                    $single = $type->singlePattern();
                    if (! in_array($single, $blocked, true)) {
                        Route::get($single, FrontendResolver::class)
                            ->defaults('page', 'entry-show')
                            ->defaults('contentType', $type->key)
                            ->name($showName);
                    }
                }
            }
        });
    }

    /**
     * @return list<string>
     */
    private static function blockedUris(): array
    {
        $keys = [
            'home_page_url',
            'categories_url',
            'single_category_url',
            'tags_url',
            'single_tag_url',
            'posts_url',
            'single_post_url',
            'page_url',
        ];

        $uris = [];
        foreach ($keys as $key) {
            $uris[] = ContentType::normalizePath(Permalink::getUri($key));
        }

        return $uris;
    }
}
