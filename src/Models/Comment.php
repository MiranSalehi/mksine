<?php

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SPAM = 'spam';

    public const STATUS_TRASH = 'trash';

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'author_name',
        'author_email',
        'content',
        'rating',
        'status',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * @return \Miran\Mksine\Database\Factories\CommentFactory
     */
    protected static function newFactory()
    {
        return \Miran\Mksine\Database\Factories\CommentFactory::new();
    }

    /**
     * Get the post that owns the comment.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Get the user who wrote the comment.
     */
    public function user(): BelongsTo
    {
        $userClass = config('mksine.user_model', \App\Models\User::class);

        return $this->belongsTo($userClass, 'user_id');
    }

    /**
     * Get the parent comment.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get the replies to this comment.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at');
    }

    /**
     * Get all descendants recursively.
     */
    public function descendants(): HasMany
    {
        return $this->replies()->with('descendants');
    }

    /**
     * Get the author name (from user or guest).
     */
    public function getAuthorDisplayNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name;
        }

        return $this->author_name ?? __('Anonymous');
    }

    /**
     * Get the author email (from user or guest).
     */
    public function getAuthorDisplayEmailAttribute(): ?string
    {
        if ($this->user) {
            return $this->user->email;
        }

        return $this->author_email;
    }

    /**
     * Check if comment is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if comment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if comment is a reply.
     */
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Check if comment has a rating.
     */
    public function hasRating(): bool
    {
        return $this->rating !== null && $this->rating > 0;
    }

    /**
     * Scope for approved comments.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for pending comments.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for root comments (not replies).
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for comments with rating.
     */
    public function scopeWithRating($query)
    {
        return $query->whereNotNull('rating')->where('rating', '>', 0);
    }

}
