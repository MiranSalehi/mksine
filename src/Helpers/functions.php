<?php

if (!function_exists('mks_setting')) {
    function mks_setting($key) {
        return \Miran\Mksine\Models\Setting::where('key', $key)->first()->value ?? null;
    }
}