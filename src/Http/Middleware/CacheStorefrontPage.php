<?php

declare(strict_types=1);

namespace Miran\Mksine\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Miran\Mksine\Core\PageCache\PageCache;
use Miran\Mksine\Http\StorefrontViewedResponder;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves anonymous storefront pages from {@see PageCache} and stores fresh renders.
 *
 * Must run inside the `web` group (after the session starts). Plugins can add it to their own
 * storefront routes with the `mksine.page-cache` alias.
 */
final class CacheStorefrontPage
{
    public function __construct(private readonly PageCache $pageCache) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->pageCache->keyFor($request);

        if ($key === null) {
            return $next($request);
        }

        $entry = $this->pageCache->get($key);

        if ($entry !== null) {
            // Analytics and other view listeners still see the visit, after the response is sent.
            if ($entry['storefront_view'] !== null) {
                $view = $entry['storefront_view'];
                app()->terminating(static fn () => StorefrontViewedResponder::emit($view, $request));
            }

            return $this->pageCache->respond($entry, $request);
        }

        $response = $next($request);

        if ($this->pageCache->isCacheable($request, $response)) {
            $view = $request->attributes->get(StorefrontViewedResponder::CONTENT_ATTRIBUTE);
            $this->pageCache->put($key, $request, $response, is_array($view) ? $view : null);
            $response->headers->set(PageCache::HEADER, 'MISS');
        } else {
            $response->headers->set(PageCache::HEADER, 'BYPASS');
        }

        return $response;
    }
}
