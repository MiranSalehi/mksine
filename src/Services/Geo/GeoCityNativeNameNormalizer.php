<?php

declare(strict_types=1);

namespace Miran\Mksine\Services\Geo;

final class GeoCityNativeNameNormalizer
{
    public static function normalizeComparable(string $value): string
    {
        $value = self::stripPersianDiacritics($value);
        $value = (string) preg_replace('/\s+/u', '', $value);

        return $value;
    }

    public static function isSafeWikidataRename(string $currentNative, string $wikidataLabel): bool
    {
        $current = trim($currentNative);
        $wiki = trim($wikidataLabel);

        if ($current === '' || $wiki === '' || $current === $wiki) {
            return false;
        }

        if (self::normalizeComparable($current) === self::normalizeComparable($wiki)) {
            if (! str_contains($current, ' ') && str_contains($wiki, ' ')) {
                return self::isCompoundWaRename($current, $wiki);
            }

            return true;
        }

        if (self::isCompoundWaRename($current, $wiki)) {
            return true;
        }

        $currentAscii = self::latinKey($current);
        $wikiAscii = self::latinKey($wiki);

        return $currentAscii !== ''
            && $currentAscii === $wikiAscii;
    }

    private static function stripPersianDiacritics(string $value): string
    {
        $map = [
            'ك' => 'ک',
            'ى' => 'ی',
            'ي' => 'ی',
            '‌' => '',
            "\u{200C}" => '',
            '١' => '۱',
            '٢' => '۲',
            '٣' => '۳',
            '٤' => '۴',
            '٥' => '۵',
            '٦' => '۶',
            '٧' => '۷',
            '٨' => '۸',
            '٩' => '۹',
            '٠' => '۰',
        ];

        return str_replace(array_keys($map), array_values($map), trim($value));
    }

    private static function isCompoundWaRename(string $current, string $wiki): bool
    {
        if (! str_contains($wiki, ' و ')) {
            return false;
        }

        $collapsedWiki = str_replace(' و ', '', $wiki);

        return self::normalizeComparable($current) === self::normalizeComparable($collapsedWiki);
    }

    private static function latinKey(string $value): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', self::stripPersianDiacritics($value));
        if (! is_string($ascii)) {
            return '';
        }

        return strtolower((string) preg_replace('/[^a-z]/', '', $ascii));
    }
}
