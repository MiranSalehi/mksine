<?php

declare(strict_types=1);

namespace Miran\Mksine\Core;

use Illuminate\Support\Facades\Schema;

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
}
