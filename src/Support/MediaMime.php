<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

final class MediaMime
{
    /**
     * @return list<string>
     */
    public static function allowedTypes(): array
    {
        return array_values(array_filter(
            array_map(static fn (mixed $type): string => trim((string) $type), (array) config('mksine.media.allowed_types', [])),
            static fn (string $type): bool => $type !== '',
        ));
    }

    public static function isAllowed(?string $mime): bool
    {
        return self::matches($mime, self::allowedTypes());
    }

    /**
     * @param  list<string>  $patterns
     */
    public static function matches(?string $mime, array $patterns): bool
    {
        if ($mime === null || $mime === '') {
            return false;
        }

        if (in_array('*', $patterns, true) || in_array('*/*', $patterns, true)) {
            return true;
        }

        if ($patterns === []) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if ($pattern === $mime) {
                return true;
            }

            if (str_ends_with($pattern, '/*')) {
                $prefix = substr($pattern, 0, -1);
                if (str_starts_with($mime, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Config allowlist entries that also match the picker's accepted patterns.
     *
     * @param  list<string>  $acceptedFileTypes
     * @return list<string>
     */
    public static function uploadableTypes(array $acceptedFileTypes): array
    {
        $allowed = self::allowedTypes();

        if (
            $acceptedFileTypes === []
            || in_array('*', $acceptedFileTypes, true)
            || in_array('*/*', $acceptedFileTypes, true)
        ) {
            return $allowed;
        }

        return array_values(array_filter(
            $allowed,
            static fn (string $mime): bool => self::matches($mime, $acceptedFileTypes),
        ));
    }

    /**
     * @param  list<string>  $acceptedFileTypes
     */
    public static function acceptsFamily(array $acceptedFileTypes, string $familyPrefix): bool
    {
        if (
            $acceptedFileTypes === []
            || in_array('*', $acceptedFileTypes, true)
            || in_array('*/*', $acceptedFileTypes, true)
        ) {
            return true;
        }

        $familyPrefix = rtrim($familyPrefix, '/').'/';

        foreach ($acceptedFileTypes as $pattern) {
            if ($pattern === $familyPrefix.'*' || str_starts_with($pattern, $familyPrefix)) {
                return true;
            }
        }

        return false;
    }
}
