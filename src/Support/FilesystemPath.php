<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

/**
 * Path helpers that tolerate mixed Windows/Unix separators.
 */
final class FilesystemPath
{
    /**
     * Return the portion of {@see $path} relative to {@see $base}.
     *
     * On Windows, {@see realpath()} and {@see glob()} may return different separator styles
     * (`\` vs `/`). A naive {@see str_replace()} then leaves an absolute path in place, which
     * later gets concatenated onto {@see lang_path()} or {@see public_path()} and fails.
     */
    /**
     * Whether {@see $path} resolves to a location strictly inside {@see $base}.
     *
     * Both operands are resolved with {@see realpath()} so symlinks cannot smuggle a
     * destination out of the base, and the base is compared with a trailing separator
     * so a sibling such as `/var/www/plugins-backup` never matches `/var/www/plugins`.
     */
    public static function isWithin(string $base, string $path): bool
    {
        $normalize = static fn (string $p): string => rtrim(str_replace('\\', '/', $p), '/');

        $realBase = realpath($base);
        $realPath = realpath($path);

        if ($realBase === false || $realPath === false) {
            return false;
        }

        $realBase = $normalize($realBase);
        $realPath = $normalize($realPath);

        if ($realBase === '' || $realPath === $realBase) {
            return false;
        }

        $prefix = $realBase.'/';

        if (DIRECTORY_SEPARATOR === '\\') {
            return str_starts_with(strtolower($realPath), strtolower($prefix));
        }

        return str_starts_with($realPath, $prefix);
    }

    public static function relativeTo(string $base, string $path): string
    {
        $normalize = static fn (string $p): string => str_replace('\\', '/', $p);

        $base = rtrim($normalize(realpath($base) ?: $base), '/');
        $path = $normalize($path);

        $prefix = $base.'/';

        if (str_starts_with($path, $prefix)) {
            return substr($path, strlen($prefix));
        }

        if (DIRECTORY_SEPARATOR === '\\' && str_starts_with(strtolower($path), strtolower($prefix))) {
            return substr($path, strlen($prefix));
        }

        if (str_starts_with($path, $base)) {
            return ltrim(substr($path, strlen($base)), '/');
        }

        return basename($path);
    }
}
