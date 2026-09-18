<?php

declare(strict_types=1);

namespace Miran\Mksine\Core;

use Illuminate\Support\Facades\Schema;
use Miran\Mksine\Core\Content\ContentTypeRegistry;

/**
 * Resolves frontend permalink URIs from settings with fallback defaults.
 * Used for dynamic route registration and URL generation.
 */
class Permalink
{
    private const DEFAULTS = [
        'home_page_url' => '/',
        'categories_url' => '/categories',
        'single_category_url' => '/category/{path}',
        'tags_url' => '/tags',
        'single_tag_url' => '/tag/{slug}',
        'posts_url' => '/posts',
        'single_post_url' => '/post/{slug}',
        'page_url' => '/page/{slug}',
    ];

    /**
     * Get the URI pattern for a permalink key (e.g. 'home_page_url', 'single_post_url').
     * Returns value from settings when available; otherwise the default.
     * Safe when settings table is missing (e.g. during migrations).
     */
    public static function getUri(string $key): string
    {
        $default = self::DEFAULTS[$key] ?? '/';

        try {
            if (! Schema::hasTable('settings')) {
                return $default;
            }

            $value = mks_setting($key);

            return $value !== null && $value !== '' ? (string) $value : $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * All default keys and their URI patterns (for validation/placeholders).
     *
     * @return array<string, string>
     */
    public static function getDefaults(): array
    {
        return self::DEFAULTS;
    }

    /**
     * Build a leading-slash path from a permalink URI pattern and placeholder map.
     *
     * @param  array<string, string>  $replacements
     */
    public static function pathFromPattern(string $pattern, array $replacements): string
    {
        $path = $pattern;
        foreach ($replacements as $key => $value) {
            $path = str_replace('{'.$key.'}', $value, $path);
        }

        $path = '/'.ltrim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path === '' ? '/' : $path;
    }

    /**
     * Storefront path for a CMS content type (post|page|registered CPT) and slug.
     */
    public static function storefrontPathForContent(string $type, string $slug): string
    {
        $key = match ($type) {
            'post' => 'single_post_url',
            'page' => 'page_url',
            default => null,
        };

        if ($key !== null) {
            return self::pathFromPattern(self::getUri($key), ['slug' => $slug]);
        }

        $definition = app(ContentTypeRegistry::class)->get($type);

        return self::pathFromPattern($definition->singlePattern(), ['slug' => $slug]);
    }

    /**
     * Archive path for posts or a registered CPT.
     */
    public static function archivePathForContent(string $type): string
    {
        if ($type === 'post') {
            return self::pathFromPattern(self::getUri('posts_url'), []);
        }

        return app(ContentTypeRegistry::class)->get($type)->archivePath();
    }
}
