<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use Illuminate\Support\Facades\File;

/**
 * Idempotently registers MksinePlugin on the app's admin panel provider (the file
 * `php artisan filament:install --panels` creates). A timestamped backup is written first.
 */
final class AdminPanelProviderPatcher
{
    public const RESULT_ALREADY = 'already';

    public const RESULT_PATCHED = 'patched';

    public const RESULT_UNSUPPORTED = 'unsupported';

    public const RESULT_MISSING = 'missing';

    private const PLUGIN_FQCN = 'Miran\\Mksine\\MksinePlugin';

    public function __construct(private readonly string $path) {}

    public static function forApp(): self
    {
        return new self(app_path('Providers/Filament/AdminPanelProvider.php'));
    }

    public function path(): string
    {
        return $this->path;
    }

    public function patch(): string
    {
        if (! File::exists($this->path)) {
            return self::RESULT_MISSING;
        }

        $contents = File::get($this->path);

        if (str_contains($contents, 'MksinePlugin::make()')) {
            return self::RESULT_ALREADY;
        }

        $withPlugin = preg_replace(
            '/(->id\(\s*[\'"]admin[\'"]\s*\))/',
            "$1\n            ->plugin(MksinePlugin::make())",
            $contents,
            1,
            $count,
        );

        if (! is_string($withPlugin) || $count !== 1) {
            return self::RESULT_UNSUPPORTED;
        }

        $patched = $this->addImport($withPlugin);

        if ($patched === null || ! self::isParseable($patched)) {
            return self::RESULT_UNSUPPORTED;
        }

        File::copy($this->path, $this->path . '.mksine-backup-' . date('YmdHis'));
        File::put($this->path, $patched);

        return self::RESULT_PATCHED;
    }

    private function addImport(string $contents): ?string
    {
        if (str_contains($contents, 'use ' . self::PLUGIN_FQCN . ';')) {
            return $contents;
        }

        $patched = preg_replace('/^(namespace\s+[^;]+;\s*\n)/m', "$1\nuse " . self::PLUGIN_FQCN . ";\n", $contents, 1, $count);

        return is_string($patched) && $count === 1 ? $patched : null;
    }

    private static function isParseable(string $code): bool
    {
        try {
            token_get_all($code, TOKEN_PARSE);

            return true;
        } catch (\ParseError) {
            return false;
        }
    }
}
