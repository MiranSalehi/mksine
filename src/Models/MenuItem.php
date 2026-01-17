<?php

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    public const TYPE_CUSTOM_LINK = 'custom_link';

    public const TYPE_CATEGORY = 'category';

    public const TYPE_PAGE = 'page';

    public const TYPE_POST = 'post';

    protected $fillable = [
        'menu_id',
        'parent_id',
        'type',
        'label',
        'url',
        'reference_id',
        'order',
        'target',
    ];

    protected $casts = [
        'order' => 'integer',
        'reference_id' => 'integer',
    ];

    /**
     * Get the menu this item belongs to.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Get the parent menu item.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * Get child menu items.
     */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('order');
    }

    /**
     * Get all descendants recursively.
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all ancestors (parent chain).
     */
    public function ancestors(): BelongsTo
    {
        return $this->parent()->with('ancestors');
    }

    /**
     * Get the resolved URL for this menu item.
     */
    public function getResolvedUrl(): ?string
    {
        if ($this->type === self::TYPE_CUSTOM_LINK) {
            return $this->url;
        }

        // For referenced items, URL resolution should be handled by the source
        return $this->url;
    }

    /**
     * Check if this item has children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get the depth level of this item in the tree.
     */
    public function getDepth(): int
    {
        $depth = 0;
        $parent = $this->parent;

        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }
}
