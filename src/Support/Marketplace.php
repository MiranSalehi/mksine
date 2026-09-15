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

    public static function hostLabel(): string
    {
        $host = parse_url(self::siteUrl(), PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : 'mksine.com';
    }
}
