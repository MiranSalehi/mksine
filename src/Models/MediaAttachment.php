<?php

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediaAttachment extends Model
{
    protected $fillable = [
        'media_id',
        'mediable_type',
        'mediable_id',
        'alt',
        'collection_name',
    ];

    /**
     * Get the media that owns this attachment.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * Get the parent mediable model (polymorphic relation).
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }
}
