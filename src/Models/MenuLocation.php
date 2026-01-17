<?php

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class MenuLocation extends Model
{
    protected $fillable = [
        'key',
        'label',
    ];

    /**
     * Get the menu assigned to this location.
     */
    public function menu(): HasOneThrough
    {
        return $this->hasOneThrough(
            Menu::class,
            MenuLocationAssignment::class,
            'menu_location_id', // Foreign key on assignments table
            'id',               // Foreign key on menus table
            'id',               // Local key on locations table
            'menu_id'           // Local key on assignments table
        );
    }

    /**
     * Get the assignment for this location.
     */
    public function assignment()
    {
        return $this->hasOne(MenuLocationAssignment::class, 'menu_location_id');
    }

    /**
     * Assign a menu to this location.
     */
    public function assignMenu(Menu $menu): MenuLocationAssignment
    {
        // Remove existing assignment
        $this->assignment()?->delete();

        return MenuLocationAssignment::create([
            'menu_id' => $menu->id,
            'menu_location_id' => $this->id,
        ]);
    }

    /**
     * Remove the menu assignment from this location.
     */
    public function unassignMenu(): bool
    {
        return (bool) $this->assignment()?->delete();
    }

    /**
     * Check if this location has a menu assigned.
     */
    public function hasMenu(): bool
    {
        return $this->assignment()->exists();
    }

    /**
     * Get a location by its key.
     */
    public static function findByKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }

    /**
     * Register default locations (seeder helper).
     */
    public static function registerDefaults(array $locations): void
    {
        foreach ($locations as $key => $label) {
            static::firstOrCreate(
                ['key' => $key],
                ['label' => $label]
            );
        }
    }
}
