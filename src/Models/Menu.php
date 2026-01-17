<?php

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Menu extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Menu $menu) {
            if (empty($menu->slug)) {
                $menu->slug = Str::slug($menu->name);
            }
        });
    }

    /**
     * Get all menu items for this menu.
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('order');
    }

    /**
     * Get root-level menu items (no parent).
     */
    public function rootItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_id')
            ->orderBy('order');
    }

    /**
     * Get locations this menu is assigned to.
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(
            MenuLocation::class,
            'menu_location_assignments',
            'menu_id',
            'menu_location_id'
        )->withTimestamps();
    }

    /**
     * Get the menu assigned to a specific location.
     */
    public static function forLocation(string $key): ?self
    {
        return static::whereHas('locations', function ($query) use ($key) {
            $query->where('key', $key);
        })->first();
    }

    /**
     * Build a nested tree structure of menu items.
     */
    public function getTree(): array
    {
        $items = $this->items()
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get();

        return $this->buildTreeRecursive($items);
    }

    /**
     * Recursively build tree structure.
     */
    protected function buildTreeRecursive($items): array
    {
        $tree = [];

        foreach ($items as $item) {
            $node = [
                'id' => $item->id,
                'type' => $item->type,
                'label' => $item->label,
                'url' => $item->url,
                'reference_id' => $item->reference_id,
                'target' => $item->target,
                'order' => $item->order,
                'children' => [],
            ];

            if ($item->children->count() > 0) {
                $node['children'] = $this->buildTreeRecursive(
                    $item->children->sortBy('order')
                );
            }

            $tree[] = $node;
        }

        return $tree;
    }
}
