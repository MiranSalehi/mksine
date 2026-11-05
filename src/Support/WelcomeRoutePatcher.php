<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use Illuminate\Support\Facades\File;

/**
 * Removes Laravel's stock `Route::get('/', ...)` welcome route from routes/web.php,
 * because MKSine serves the home page at `/`. Any other `/` route is left alone.
 */
final class WelcomeRoutePatcher
{
    public const RESULT_ABSENT = 'absent';

    public const RESULT_PATCHED = 'patched';

    public const RESULT_MISSING = 'missing';

    private const WELCOME_ROUTE_PATTERN = '/^[ \t]*Route::(?:get\(\s*[\'"]\/[\'"]\s*,\s*function\s*\(\s*\)\s*(?::\s*\w+\s*)?\{\s*return\s+view\(\s*[\'"]welcome[\'"]\s*\);\s*\}\s*\)|view\(\s*[\'"]\/[\'"]\s*,\s*[\'"]welcome[\'"]\s*\))(?:\s*->name\(\s*[\'"]home[\'"]\s*\))?\s*;[ \t]*\R?/m';

    public function __construct(private readonly string $path) {}

    public static function forApp(): self
    {
        return new self(base_path('routes/web.php'));
    }

    public function patch(): string
    {
        if (! File::exists($this->path)) {
            return self::RESULT_MISSING;
        }

        $contents = File::get($this->path);
        $patched = preg_replace(self::WELCOME_ROUTE_PATTERN, '', $contents, 1, $count);

        if (! is_string($patched) || $count === 0) {
            return self::RESULT_ABSENT;
        }

        File::copy($this->path, $this->path . '.mksine-backup-' . date('YmdHis'));
        File::put($this->path, $patched);

        return self::RESULT_PATCHED;
    }
}
