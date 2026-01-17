<?php

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuLocationAssignment extends Model
{
    protected $fillable = [
        'menu_id',
        'menu_location_id',
    ];

    /**
     * Get the menu.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Get the location.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(MenuLocation::class, 'menu_location_id');
    }
}
