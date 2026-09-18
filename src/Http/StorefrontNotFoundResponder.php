<?php

declare(strict_types=1);

namespace Miran\Mksine\Http;

use Illuminate\Http\Request;
use Miran\Mksine\Core\Events\Storefront\StorefrontNotFound;
use Miran\Mksine\Core\Hooks\HookManager;
use Miran\Mksine\Core\Hooks\Hooks;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Dispatches storefront 404 hooks and optionally returns a plugin-supplied response.
 */
final class StorefrontNotFoundResponder
{
    public static function response(Request $request): ?Response
    {
        if (! StorefrontRequest::isStorefront($request)) {
            return null;
        }

        $path = StorefrontRequest::normalizedPath($request);

        $event = new StorefrontNotFound([
            'path' => $path,
            'full_url' => $request->fullUrl(),
            'method' => $request->method(),
        ], [
            'ip' => $request->ip(),
        ]);

        try {
            app(HookManager::class)->dispatch($event);
        } catch (Throwable $e) {
            report($e);
        }

        try {
            $filtered = Hooks::filter(StorefrontNotFound::FILTER, null, $request, $path);
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        return $filtered instanceof Response ? $filtered : null;
    }
}
