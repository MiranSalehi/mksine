<?php

declare(strict_types=1);

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Miran\Mksine\Enums\GeoSource;

class GeoCity extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'geo_state_id',
        'geo_country_id',
        'name',
        'native',
        'translations',
        'source',
        'is_visible',
        'latitude',
        'longitude',
        'timezone',
        'wiki_data_id',
        'type',
        'population',
    ];

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'source' => GeoSource::class,
            'is_visible' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'population' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<GeoState, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(GeoState::class, 'geo_state_id');
    }

    /**
     * @return BelongsTo<GeoCountry, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(GeoCountry::class, 'geo_country_id');
    }

    public function isDeletable(): bool
    {
        return $this->source === GeoSource::Manual;
    }
}
