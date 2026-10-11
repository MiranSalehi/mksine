<?php

namespace Miran\Mksine\Core\PageBuilder;

final class BuilderUrl
{
    /**
     * @var list<string>
     */
    private const ALLOWED_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /**
     * Allow http(s), mailto, tel, a site path, or an in-page anchor.
     * Anything else, including javascript: and data:, becomes an inert hash.
     */
    public static function href(mixed $url): string
    {
        if (! is_string($url)) {
            return '#';
        }

        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (self::isSitePath($url) || self::isAnchor($url)) {
            return $url;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (! is_string($scheme) || ! in_array(strtolower($scheme), self::ALLOWED_SCHEMES, true)) {
            return '#';
        }

        return $url;
    }

    /**
     * Heading tags are an allowlist. A stored payload must not choose the element name.
     */
    public static function headingLevel(mixed $level): string
    {
        if (! is_string($level)) {
            return 'h2';
        }

        $level = strtolower(trim($level));

        return in_array($level, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) ? $level : 'h2';
    }

    private static function isSitePath(string $url): bool
    {
        return str_starts_with($url, '/')
            && ! str_starts_with($url, '//')
            && ! str_starts_with($url, '/\\');
    }

    private static function isAnchor(string $url): bool
    {
        return str_starts_with($url, '#') && ! str_contains($url, ':');
    }
}
