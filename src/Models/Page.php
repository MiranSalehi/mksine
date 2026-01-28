<?php

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'type',
        'content',
        'builder_payload',
    ];

    protected $casts = [
        'builder_payload' => 'array',
    ];
}
