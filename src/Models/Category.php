<?php

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'parent_id',
        'sort_order',
        'is_active',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get all posts in this category.
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'category_post')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Miran\Mksine\Database\Factories\CategoryFactory::new();
    }

    /**
     * Get the category image.
     */
    public function categoryImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'image');
    }

    /**
     * Get breadcrumb path from root to this category (parent → child order, like WordPress).
     *
     * @return \Illuminate\Support\Collection<int, Category>
     */
    public function getBreadcrumbPath(): \Illuminate\Support\Collection
    {
        $path = collect();
        $current = $this;

        while ($current) {
            $path->prepend($current);
            $next = null;
            if ($current->parent_id) {
                $next = $current->relationLoaded('parent') ? $current->parent : $current->parent()->first();
            }
            $current = $next;
        }

        return $path;
    }

    /**
     * Get full slug path from root to this category (parent/child/grandchild), recursive.
     * Used for hierarchical URLs like WordPress.
     */
    public function getFullSlug(): string
    {
        $parent = $this->parent_id
            ? ($this->relationLoaded('parent') ? $this->parent : $this->parent()->first())
            : null;

        if (! $parent) {
            return $this->slug;
        }

        return $parent->getFullSlug() . '/' . $this->slug;
    }

    /**
     * Get the frontend URL path for this category (hierarchical).
     */
    public function getUrl(): string
    {
        return route('categories.show', ['path' => $this->getFullSlug()]);
    }

    /**
     * Get category tree for select/checkbox UI: roots with children, active only, ordered by sort_order.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Category>
     */
    public static function getTreeForSelect(): \Illuminate\Database\Eloquent\Collection
    {
        return static::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->with(['children' => fn (HasMany $q) => $q->where('is_active', true)->orderBy('sort_order')])
            ->get();
    }

    /**
     * Find a category by its full path slug (e.g. "health" or "health/nutrition").
     *
     * @param  string  $path  Slash-separated slug path from root to leaf
     * @return Category|null
     */
    public static function findByFullPath(string $path): ?Category
    {
        $segments = array_values(array_filter(explode('/', $path)));
        if ($segments === []) {
            return null;
        }

        $current = static::whereNull('parent_id')->where('slug', $segments[0])->first();
        if (! $current) {
            return null;
        }

        for ($i = 1, $n = count($segments); $i < $n; $i++) {
            $current = $current->children()->where('slug', $segments[$i])->first();
            if (! $current) {
                return null;
            }
        }

        return $current;
    }
}
