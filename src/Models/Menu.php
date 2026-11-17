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
     *
     * Loads every item of the menu in one query and nests them in PHP (eager loading only went
     * one level deep, so each deeper level cost one query per item).
     *
     * @return list<array<string, mixed>>
     */
    public function getTree(): array
    {
        $childrenByParent = [];

        foreach ($this->items()->get() as $item) {
            $childrenByParent[(int) ($item->parent_id ?? 0)][] = $item;
        }

        return $this->buildTreeRecursive($childrenByParent, 0, []);
    }

    /**
     * @param  array<int, list<MenuItem>>  $childrenByParent
     * @param  array<int, true>  $ancestors  Guards against parent cycles in stored data.
     * @return list<array<string, mixed>>
     */
    protected function buildTreeRecursive(array $childrenByParent, int $parentId, array $ancestors): array
    {
        $tree = [];

        foreach ($childrenByParent[$parentId] ?? [] as $item) {
            $id = (int) $item->id;

            if (isset($ancestors[$id])) {
                continue;
            }

            $tree[] = [
                'id' => $item->id,
                'type' => $item->type,
                'label' => $item->label,
                'url' => MenuItem::safeUrl($item->url),
                'reference_id' => $item->reference_id,
                'target' => $item->target,
                'order' => $item->order,
                'children' => $this->buildTreeRecursive($childrenByParent, $id, $ancestors + [$id => true]),
            ];
        }

        return $tree;
    }
}
