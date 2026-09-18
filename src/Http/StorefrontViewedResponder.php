<?php

declare(strict_types=1);

namespace Miran\Mksine\Http;

use Illuminate\Http\Request;
use Livewire\Livewire;
use Miran\Mksine\Core\Events\Storefront\StorefrontViewed;
use Miran\Mksine\Core\Hooks\HookManager;
use Miran\Mksine\Core\Hooks\Hooks;
use Throwable;

/**
 * Dispatches {@see StorefrontViewed} once per public GET after content is resolved.
 */
final class StorefrontViewedResponder
{
    /**
     * @param  array{type?: string|null, id?: int|string|null, status?: string|null}  $content
     */
    public static function emit(array $content = [], ?Request $request = null): void
    {
        $request ??= request();

        if (! StorefrontRequest::isStorefront($request) || $request->method() !== 'GET') {
            return;
        }

        if (self::isLivewireFollowUp($request)) {
            return;
        }

        if ($request->attributes->get('mksine.storefront_viewed')) {
            return;
        }

        $request->attributes->set('mksine.storefront_viewed', true);

        $path = StorefrontRequest::normalizedPath($request);
        $id = $content['id'] ?? null;

        $event = new StorefrontViewed([
            'path' => $path,
            'content_type' => $content['type'] ?? null,
            'content_id' => is_numeric($id) ? (int) $id : $id,
            'status' => $content['status'] ?? null,
        ], [
            'referrer' => $request->headers->get('referer'),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            app(HookManager::class)->dispatch($event);
        } catch (Throwable $e) {
            report($e);
        }

        try {
            Hooks::filter(StorefrontViewed::FILTER, $event, $request);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private static function isLivewireFollowUp(Request $request): bool
    {
        if ($request->hasHeader('X-Livewire')) {
            return true;
        }

        try {
            return Livewire::isLivewireRequest();
        } catch (Throwable) {
            return false;
        }
    }
}
