<?php

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Miran\Mksine\Support\SettingStore;

class Setting extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;

    protected static function booted()
    {
        // Clear setting cache when a setting is saved or deleted
        static::saved(function ($setting) {
            Cache::forget("setting_{$setting->key}");
            app(SettingStore::class)->flush();
        });

        static::deleted(function ($setting) {
            Cache::forget("setting_{$setting->key}");
            app(SettingStore::class)->flush();
        });
    }
}

