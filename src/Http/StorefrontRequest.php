<?php

declare(strict_types=1);

namespace Miran\Mksine\Http;

use Illuminate\Http\Request;

/**
 * Detects public CMS requests that plugins may treat as storefront (404, analytics).
 */
final class StorefrontRequest
{
    /**
     * @return list<string>
     */
    public static function excludedPathPatterns(): array
    {
        $admin = trim((string) config('mksine.admin_path', 'admin'), '/');
        if ($admin === '') {
            $admin = 'admin';
        }

        return [
            $admin,
            $admin.'/*',
            'livewire/*',
            'api',
            'api/*',
            'storage/*',
            'vendor/livewire/*',
            '_ignition/*',
            'horizon',
            'horizon/*',
            'telescope',
            'telescope/*',
            'up',
        ];
    }

    public static function isStorefront(Request $request): bool
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return false;
        }

        if ($request->expectsJson()) {
            return false;
        }

        foreach (self::excludedPathPatterns() as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        return true;
    }

    public static function normalizedPath(Request $request): string
    {
        $path = '/'.ltrim($request->getPathInfo(), '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return $path === '' ? '/' : $path;
    }
}
