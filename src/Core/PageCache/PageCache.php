<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\PageCache;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Miran\Mksine\Core\Hooks\Hooks;
use Miran\Mksine\Http\Middleware\CacheStorefrontPage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Full-page HTML cache for anonymous storefront GET requests.
 *
 * Runs after the session starts (see {@see CacheStorefrontPage}), so
 * every visitor keeps their own session and CSRF token: the token is swapped for a placeholder
 * when a page is stored and for the visitor's own token when it is served. Entries are keyed by
 * a version number that any content change bumps ({@see PageCacheInvalidator}), so stale pages
 * are never served; old entries expire with the TTL.
 *
 * A page is not cached when the visitor is signed in, has flashed session data or old input,
 * sends a query parameter that is not allowlisted, or when anything during the render calls
 * {@see skip()} (e.g. a cart badge with items), sets a cookie other than the session/XSRF ones,
 * or sends `X-Mksine-No-Cache`. Plugins can veto or vary pages through the
 * `mksine.page_cache.should_cache` and `mksine.page_cache.key_parts` filters.
 */
final class PageCache
{
    public const string HEADER = 'X-Mksine-Cache';

    public const string NO_CACHE_HEADER = 'X-Mksine-No-Cache';

    public const string SETTING_ENABLED = 'page_cache_enabled';

    public const string SETTING_TTL_MINUTES = 'page_cache_ttl_minutes';

    public const string FILTER_SHOULD_CACHE = 'mksine.page_cache.should_cache';

    public const string FILTER_KEY_PARTS = 'mksine.page_cache.key_parts';

    private const string VERSION_KEY = 'mksine.page_cache.version';

    private const string SKIP_ATTRIBUTE = 'mksine.page_cache.skip';

    private const string CSRF_PLACEHOLDER = '__MKSINE_PAGE_CACHE_CSRF__';

    /** Pages larger than this are not stored. */
    private const int MAX_BODY_BYTES = 2_000_000;

    private bool $flushedThisRequest = false;

    /**
     * Mark the current page as personal: it is rendered normally and never stored.
     */
    public static function skip(?Request $request = null): void
    {
        ($request ?? request())->attributes->set(self::SKIP_ATTRIBUTE, true);
    }

    public function isEnabled(): bool
    {
        $stored = mks_setting(self::SETTING_ENABLED);

        // A saved toggle (even '' / '0' for off) wins over the config default.
        if ($stored !== null) {
            return filter_var($stored, FILTER_VALIDATE_BOOLEAN);
        }

        return (bool) config('mksine.page_cache.enabled', false);
    }

    public function ttlSeconds(): int
    {
        $minutes = mks_setting(self::SETTING_TTL_MINUTES);

        if (is_numeric($minutes) && (int) $minutes > 0) {
            return (int) $minutes * 60;
        }

        return max(60, (int) config('mksine.page_cache.ttl', 3600));
    }

    /**
     * Cache key for this request, or null when the request must not use the cache.
     */
    public function keyFor(Request $request): ?string
    {
        if (! $this->isEnabled() || ! $request->isMethodCacheable()) {
            return null;
        }

        if ($request->hasHeader('X-Livewire') || $this->isSignedIn() || $this->hasPersonalSessionData($request)) {
            return null;
        }

        $query = $this->cacheableQuery($request);

        if ($query === null || $this->matchesBypassRules($request)) {
            return null;
        }

        try {
            if (Hooks::filter(self::FILTER_SHOULD_CACHE, true, $request) !== true) {
                return null;
            }

            /** @var array<string, mixed> $parts */
            $parts = Hooks::filter(self::FILTER_KEY_PARTS, [
                'url' => $request->getSchemeAndHttpHost() . '/' . ltrim($request->path(), '/'),
                'query' => $query,
                'locale' => app()->getLocale(),
            ], $request);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }

        return 'mksine.page:' . $this->version() . ':' . sha1((string) json_encode($parts));
    }

    /**
     * The stored page for this key, or null on a miss.
     *
     * @return array{body: string, content_type: string, created_at: int, storefront_view: array<string, mixed>|null}|null
     */
    public function get(string $key): ?array
    {
        try {
            $entry = $this->store()->get($key);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($entry) || ! is_string($entry['body'] ?? null)) {
            return null;
        }

        return [
            'body' => $entry['body'],
            'content_type' => (string) ($entry['content_type'] ?? 'text/html; charset=UTF-8'),
            'created_at' => (int) ($entry['created_at'] ?? time()),
            'storefront_view' => is_array($entry['storefront_view'] ?? null) ? $entry['storefront_view'] : null,
        ];
    }

    /**
     * Response for a stored page, carrying this visitor's own CSRF token.
     *
     * @param  array{body: string, content_type: string, created_at: int, storefront_view: array<string, mixed>|null}  $entry
     */
    public function respond(array $entry, Request $request): Response
    {
        $token = $request->hasSession() ? (string) $request->session()->token() : '';

        $response = new \Illuminate\Http\Response(
            str_replace(self::CSRF_PLACEHOLDER, e($token), $entry['body']),
            200,
            ['Content-Type' => $entry['content_type']],
        );

        $response->headers->set(self::HEADER, 'HIT');
        $response->headers->set('Age', (string) max(0, time() - $entry['created_at']));

        // Livewire adds its scripts and styles after the middleware chain, and only when a
        // component rendered in this request; on a hit none did, so ask for them explicitly.
        if (str_contains($entry['body'], 'wire:snapshot') && class_exists(Livewire::class)) {
            Livewire::forceAssetInjection();
        }

        return $response;
    }

    public function isCacheable(Request $request, Response $response): bool
    {
        if ($request->attributes->get(self::SKIP_ATTRIBUTE) === true || $response->headers->has(self::NO_CACHE_HEADER)) {
            return false;
        }

        if ($response->getStatusCode() !== 200 || $response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return false;
        }

        if (! str_contains(strtolower((string) $response->headers->get('Content-Type', '')), 'text/html')) {
            return false;
        }

        $body = $response->getContent();

        if (! is_string($body) || $body === '' || strlen($body) > self::MAX_BODY_BYTES) {
            return false;
        }

        // The render may have signed someone in or stored personal data.
        if ($this->isSignedIn() || $this->hasPersonalSessionData($request)) {
            return false;
        }

        $allowedCookies = [(string) config('session.cookie'), 'XSRF-TOKEN'];

        foreach ($response->headers->getCookies() as $cookie) {
            if (! in_array($cookie->getName(), $allowedCookies, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>|null  $storefrontView
     */
    public function put(string $key, Request $request, Response $response, ?array $storefrontView = null): void
    {
        $body = (string) $response->getContent();
        $token = $request->hasSession() ? (string) $request->session()->token() : '';

        if ($token !== '') {
            $body = str_replace($token, self::CSRF_PLACEHOLDER, $body);
        }

        try {
            $this->store()->put($key, [
                'body' => $body,
                'content_type' => (string) $response->headers->get('Content-Type', 'text/html; charset=UTF-8'),
                'created_at' => time(),
                'storefront_view' => $storefrontView,
            ], $this->ttlSeconds());
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * Invalidate every stored page (at most once per request, so bulk saves stay cheap).
     */
    public function flush(): void
    {
        if ($this->flushedThisRequest) {
            return;
        }

        $this->flushedThisRequest = true;

        try {
            $this->store()->forever(self::VERSION_KEY, (string) hrtime(true) . random_int(100, 999));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function version(): string
    {
        try {
            return (string) ($this->store()->get(self::VERSION_KEY) ?? '0');
        } catch (Throwable) {
            return '0';
        }
    }

    private function store(): Repository
    {
        $store = config('mksine.page_cache.store');

        return is_string($store) && $store !== '' ? Cache::store($store) : Cache::store();
    }

    /**
     * Sorted allowlisted query string, '' for none, or null when an unknown parameter is present.
     */
    private function cacheableQuery(Request $request): ?string
    {
        /** @var list<string> $allowed */
        $allowed = (array) config('mksine.page_cache.cacheable_query_params', ['page']);
        /** @var list<string> $ignored */
        $ignored = (array) config('mksine.page_cache.ignored_query_params', []);

        $kept = [];

        foreach ($request->query() as $name => $value) {
            $name = (string) $name;

            if (in_array($name, $ignored, true) || str_starts_with($name, 'utm_')) {
                continue;
            }

            if (! in_array($name, $allowed, true) || ! is_scalar($value)) {
                return null;
            }

            $kept[$name] = (string) $value;
        }

        ksort($kept);

        return http_build_query($kept);
    }

    private function matchesBypassRules(Request $request): bool
    {
        foreach ((array) config('mksine.page_cache.bypass_paths', []) as $pattern) {
            if (is_string($pattern) && $pattern !== '' && $request->is(ltrim($pattern, '/'))) {
                return true;
            }
        }

        foreach ((array) config('mksine.page_cache.bypass_cookies', []) as $cookie) {
            if (is_string($cookie) && $request->cookies->has($cookie)) {
                return true;
            }
        }

        if ($request->hasSession()) {
            foreach ((array) config('mksine.page_cache.bypass_session_keys', []) as $sessionKey) {
                if (is_string($sessionKey) && $request->session()->has($sessionKey)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isSignedIn(): bool
    {
        foreach ((array) config('auth.guards', []) as $name => $guard) {
            if (($guard['driver'] ?? null) !== 'session') {
                continue;
            }

            try {
                if (Auth::guard((string) $name)->check()) {
                    return true;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return false;
    }

    private function hasPersonalSessionData(Request $request): bool
    {
        if (! $request->hasSession()) {
            return false;
        }

        $session = $request->session();

        return $session->has('errors')
            || $session->has('_old_input')
            || (array) $session->get('_flash.old', []) !== []
            || (array) $session->get('_flash.new', []) !== [];
    }
}
