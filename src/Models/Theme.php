<?php

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    protected $fillable = [
        'identifier',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get the currently active theme.
     */
    public static function active(): ?self
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Activate this theme (deactivates all others).
     */
    public function activate(): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () {
            // Deactivate all themes
            static::query()->update(['is_active' => false]);

            // Activate this theme
            return $this->update(['is_active' => true]);
        });
    }

    /**
     * Check if this theme is the active theme.
     */
    public function isActive(): bool
    {
        return $this->is_active === true;
    }
}
