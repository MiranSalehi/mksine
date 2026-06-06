<?php

declare(strict_types=1);

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Miran\Mksine\Enums\GeoSource;

class GeoState extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'geo_country_id',
        'code',
        'name',
        'native',
        'translations',
        'source',
        'is_visible',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'translations' => 'array',
            'source' => GeoSource::class,
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<GeoCountry, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(GeoCountry::class, 'geo_country_id');
    }

    /**
     * @return HasMany<GeoCity, $this>
     */
    public function cities(): HasMany
    {
        return $this->hasMany(GeoCity::class, 'geo_state_id')->orderBy('name');
    }

    public function isDeletable(): bool
    {
        return $this->source === GeoSource::Manual;
    }
}
