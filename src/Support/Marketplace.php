<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

final class Marketplace
{
    public static function siteUrl(): string
    {
        $url = rtrim((string) config('mksine.marketplace.url', 'https://mksine.com'), '/');

        return $url !== '' ? $url : 'https://mksine.com';
    }

    public static function directoryUrl(): string
    {
        $configured = rtrim(trim((string) config('mksine.marketplace.directory_url', '')), '/');

        if ($configured !== '') {
            return $configured;
        }

        return self::siteUrl().'/marketplace';
    }

    public static function apiUrl(): string
    {
        $configured = rtrim(trim((string) config('mksine.marketplace.api_url', '')), '/');

        if ($configured !== '') {
            return $configured;
        }

        return self::siteUrl().'/api/marketplace/v1';
    }

    public static function pluginsDirectoryUrl(): string
    {
        return self::directoryUrl().'/plugins';
    }

    public static function themesDirectoryUrl(): string
    {
        return self::directoryUrl().'/themes';
    }

    public static function hostLabel(): string
    {
        $host = parse_url(self::siteUrl(), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : 'mksine.com';
    }

    public static function userAgent(): string
    {
        $version = (string) config('mksine.version', 'dev');

        return 'MKSine-CMS/'.$version.' (+https://mksine.com)';
    }
}
