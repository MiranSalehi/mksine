<?php

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Miran\Mksine\Traits\HasMediaAttachments;

class Post extends Model
{
    use HasFactory;
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
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Miran\Mksine\Database\Factories\PostFactory::new();
    }

    /**
     * Get the featured image.
     */
    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image');
    }

    /**
     * Get all comments for this post.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get only approved comments for this post.
     */
    public function approvedComments(): HasMany
    {
        return $this->hasMany(Comment::class)
            ->where('status', Comment::STATUS_APPROVED)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get average rating from approved comments.
     */
    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->comments()
            ->where('status', Comment::STATUS_APPROVED)
            ->whereNotNull('rating')
            ->where('rating', '>', 0)
            ->avg('rating');

        return $avg ? round($avg, 1) : null;
    }

    /**
     * Get total rating count from approved comments.
     */
    public function getRatingCountAttribute(): int
    {
        return $this->comments()
            ->where('status', Comment::STATUS_APPROVED)
            ->whereNotNull('rating')
            ->where('rating', '>', 0)
            ->count();
    }
}
