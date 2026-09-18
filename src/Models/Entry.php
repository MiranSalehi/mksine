<?php

declare(strict_types=1);

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Miran\Mksine\Core\Content\ContentType;
use Miran\Mksine\Core\Content\ContentTypeRegistry;
use Miran\Mksine\Core\Permalink;
use Miran\Mksine\Observers\ContentSlugObserver;
use Miran\Mksine\Traits\HasMediaAttachments;
use Miran\Mksine\Traits\HasTags;

#[ObservedBy([ContentSlugObserver::class])]
class Entry extends Model
{
    use HasFactory;
    use HasMediaAttachments;
    use HasTags;
    use SoftDeletes;

    protected $table = 'mks_entries';

    protected $fillable = [
        'content_type',
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
        'focus_keyphrase',
        'views_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'views_count' => 'integer',
        ];
    }

    protected static function newFactory(): \Miran\Mksine\Database\Factories\EntryFactory
    {
        return \Miran\Mksine\Database\Factories\EntryFactory::new();
    }

    public function author(): BelongsTo
    {
        $userClass = config('mksine.user_model', \App\Models\User::class);

        return $this->belongsTo($userClass, 'author_id');
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_image');
    }

    public function contentTypeDefinition(): ContentType
    {
        return app(ContentTypeRegistry::class)->get((string) $this->content_type);
    }

    public function url(): string
    {
        return Permalink::storefrontPathForContent((string) $this->content_type, (string) $this->slug);
    }

    /**
     * @param  Builder<Entry>  $query
     * @return Builder<Entry>
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('content_type', $type);
    }

    /**
     * @param  Builder<Entry>  $query
     * @return Builder<Entry>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
