<?php

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Miran\Mksine\Traits\HasMediaAttachments;

class Post extends Model
{
    use HasMediaAttachments;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'featured_image',
        'author_id',
        'published_at',
        'meta_title',
        'meta_description',
        'views_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'views_count' => 'integer',
    ];

    /**
     * Get the author of the post.
     */
    public function author(): BelongsTo
    {
        $userClass = config('mksine.user_model', \App\Models\User::class);

        return $this->belongsTo($userClass, 'author_id');
    }

    /**
     * Get all categories for this post.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_post')
            ->withPivot('sort_order')
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * Get the featured image.
     */
    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image');
    }
}
