<?php

declare(strict_types=1);

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeoCountry extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'iso2',
        'iso3',
        'name',
        'native',
        'translations',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<GeoState, $this>
     */
    public function states(): HasMany
    {
        return $this->hasMany(GeoState::class, 'geo_country_id')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @return HasMany<GeoCity, $this>
     */
    public function cities(): HasMany
    {
        return $this->hasMany(GeoCity::class, 'geo_country_id');
    }
}
