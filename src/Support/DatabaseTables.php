<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * `Schema::hasTable()` with the positive answers remembered for the rest of the request.
 *
 * Boot code asks whether `mks_plugins` / `mks_hooks` exist many times per request; each answer is
 * an information_schema query, slow on shared MySQL. Only "exists" is remembered: a table can
 * appear mid-request (installer, migrations) but does not vanish. Bound as a scoped instance.
 */
final class DatabaseTables
{
    /** @var array<string, true> */
    private array $existing = [];

    public static function exists(string $table): bool
    {
        return app(self::class)->check($table);
    }

    public function check(string $table): bool
    {
        if (isset($this->existing[$table])) {
            return true;
        }

        try {
            if (! app()->bound('db') || ! Schema::hasTable($table)) {
                return false;
            }
        } catch (Throwable) {
            return false;
        }

        $this->existing[$table] = true;

        return true;
    }
}
