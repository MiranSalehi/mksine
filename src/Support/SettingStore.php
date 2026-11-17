<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use Illuminate\Support\Facades\Cache;
use Miran\Mksine\Models\Setting;
use Throwable;

/**
 * Every `settings` row, read once per request (and kept in the cache between requests).
 *
 * `mks_setting()` used to run one query per call, which added up to 37 queries on an admin page
 * and 2–4 per formatted date. The table is small (a few dozen rows), so loading it whole is
 * cheaper than any single lookup. Bound as a scoped instance so queue workers and Octane see
 * fresh values per job/request; {@see Setting} flushes the cache whenever a row is saved or deleted,
 * and so do migrations.
 */
final class SettingStore
{
    public const string CACHE_KEY = 'mksine.settings.all';

    /** @var array<string, mixed>|null */
    private ?array $values = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->all();

        return array_key_exists($key, $values) ? $values[$key] : $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->values ??= $this->load();
    }

    /**
     * Forget the values in memory and in the cache (after any write to `settings`).
     */
    public function flush(): void
    {
        $this->values = null;

        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable) {
            // Cache store not usable yet (installer, missing cache table): memory is enough.
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function load(): array
    {
        try {
            /** @var array<string, mixed> */
            return Cache::rememberForever(self::CACHE_KEY, fn (): array => $this->query());
        } catch (Throwable) {
            return $this->query();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function query(): array
    {
        try {
            /** @var array<string, mixed> */
            return Setting::query()->pluck('value', 'key')->all();
        } catch (Throwable) {
            // Table not migrated yet (install, fresh test database).
            return [];
        }
    }
}
