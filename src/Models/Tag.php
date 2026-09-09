<?php

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Miran\Mksine\Core\Permalink;
use Miran\Mksine\Database\Factories\TagFactory;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class Tag extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all posts with this tag.
     */
    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'taggable')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Get all pages with this tag.
     */
    public function pages(): MorphToMany
    {
        return $this->morphedByMany(Page::class, 'taggable')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return TagFactory::new();
    }

    /**
     * Find an existing tag by name or create one with a unique slug.
     */
    public static function findOrCreateFromName(string $name): self
    {
        $name = trim($name);

        if ($name === '') {
            throw new \InvalidArgumentException('Tag name cannot be empty.');
        }

        $existing = static::query()->where('name', $name)->first();
        if ($existing) {
            return $existing;
        }

        $baseSlug = Str::slug($name);
        if ($baseSlug === '') {
            $baseSlug = 'tag';
        }

        $slug = $baseSlug;
        $i = 2;
        while (static::query()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$i;
            $i++;
        }

        return static::query()->create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    /**
     * Get the frontend URL for this tag.
     *
     * Falls back to a manually-constructed URL when the named route is not registered.
     */
    public function getUrl(): string
    {
        try {
            return route('tags.show', ['slug' => $this->slug]);
        } catch (RouteNotFoundException) {
            $uri = Permalink::getUri('single_tag_url');
            $path = str_replace('{slug}', ltrim((string) $this->slug, '/'), $uri);

            return url($path);
        }
    }
}
