<?php

declare(strict_types=1);

namespace Miran\Mksine\Support\Frontend;

use Illuminate\Support\Facades\Route;

final class StorefrontUrl
{
    public static function resolve(): string
    {
        if (Route::has('ecom.shop')) {
            return route('ecom.shop');
        }

        if (Route::has('home')) {
            return route('home');
        }

        return url('/');
    }

    public static function siteLabel(): string
    {
        $name = mks_setting('site_name');

        if (is_string($name) && trim($name) !== '') {
            return trim($name);
        }

        return (string) config('app.name', 'MKSine');
    }
}
