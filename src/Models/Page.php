<?php

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Miran\Mksine\Database\Factories\PageFactory;

class Page extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'type',
        'status',
        'content',
        'builder_payload',
        'show_page_header',
        'builder_content_width',
        'meta_title',
        'meta_description',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'builder_payload' => 'array',
        'published_at' => 'datetime',
        'show_page_header' => 'boolean',
    ];

    /**
     * Get the user who created this page.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    /**
     * Get the user who last updated this page.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by');
    }

    /**
     * Check if page is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if page is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if page is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    /**
     * Check if page uses builder.
     */
    public function usesBuilder(): bool
    {
        return $this->type === 'builder';
    }

    /**
     * Scope for published pages.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return PageFactory::new();
    }

    /**
     * Scope for draft pages.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Duplicate this page as a new draft (unique slug, same content / builder payload).
     */
    public function duplicateAsDraft(?int $userId = null): self
    {
        $userId ??= auth()->id();

        $payload = $this->builder_payload;
        if (is_array($payload)) {
            $payload = json_decode(json_encode($payload), true);
        }

        return static::query()->create([
            'title' => $this->title.' '.__('mksine::pages.duplicate_title_suffix'),
            'slug' => static::uniqueCopySlug($this->slug),
            'type' => $this->type,
            'status' => 'draft',
            'content' => $this->content,
            'builder_payload' => $payload,
            'show_page_header' => $this->show_page_header ?? true,
            'builder_content_width' => $this->builder_content_width ?? 'contained',
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'published_at' => null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public static function uniqueCopySlug(string $baseSlug): string
    {
        $baseSlug = trim($baseSlug) !== '' ? trim($baseSlug) : 'page';

        $candidate = "{$baseSlug}-copy";
        if (! static::query()->withTrashed()->where('slug', $candidate)->exists()) {
            return $candidate;
        }

        $i = 2;
        while (static::query()->withTrashed()->where('slug', "{$baseSlug}-copy-{$i}")->exists()) {
            $i++;
        }

        return "{$baseSlug}-copy-{$i}";
    }
}
