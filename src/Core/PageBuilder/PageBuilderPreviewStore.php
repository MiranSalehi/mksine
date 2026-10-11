<?php

namespace Miran\Mksine\Core\PageBuilder;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Short-lived, per-user copy of the unsaved builder payload for the iframe.
 */
final class PageBuilderPreviewStore
{
    public function put(int $userId, array $payload): string
    {
        $token = Str::random(40);

        Cache::put($this->key($userId, $token), $payload, now()->addMinutes(10));

        return $token;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    public function get(int $userId, string $token): ?array
    {
        $payload = Cache::get($this->key($userId, $token));

        return is_array($payload) ? $payload : null;
    }

    private function key(int $userId, string $token): string
    {
        return 'mksine.page-builder.preview.'.$userId.'.'.$token;
    }
}
