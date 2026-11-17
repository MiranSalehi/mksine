<?php

declare(strict_types=1);

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Miran\Mksine\Core\Media\ImageConversions;
use Miran\Mksine\Jobs\GenerateMediaConversions;
use Miran\Mksine\Support\MediaStoredFile;

class Media extends Model
{
    use SoftDeletes;

    protected $table = 'media';

    /**
     * The attributes that are mass assignable.
     * Using explicit $fillable instead of $guarded = [] for security.
     */
    protected $fillable = [
        'name',
        'file_name',
        'mime_type',
        'size',
        'width',
        'height',
        'disk',
        'path',
        'url',
        'alt',
        'title',
        'caption',
        'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'uploaded_by' => 'integer',
        'conversions' => 'array',
    ];

    protected static function booted(): void
    {
        // Responsive WebP copies (see ImageConversions): built after upload, rebuilt when the
        // file is replaced, removed when the media item is permanently deleted.
        static::created(static function (Media $media): void {
            if (app(ImageConversions::class)->canConvert($media)) {
                GenerateMediaConversions::dispatchFor($media);
            }
        });

        static::updated(static function (Media $media): void {
            if (! $media->wasChanged('path')) {
                return;
            }

            $previous = $media->getOriginal('conversions');
            app(ImageConversions::class)->delete($media, is_array($previous) ? $previous : null);

            if ($media->conversions !== null) {
                $media->forceFill(['conversions' => null])->saveQuietly();
            }

            if (app(ImageConversions::class)->canConvert($media)) {
                GenerateMediaConversions::dispatchFor($media);
            }
        });

        static::forceDeleted(static function (Media $media): void {
            app(ImageConversions::class)->delete($media);
        });
    }

    /**
     * Get all media attachments for this media.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MediaAttachment::class);
    }

    /**
     * Get the uploader of the media.
     */
    public function uploader()
    {
        $userClass = config('mksine.user_model', \App\Models\User::class);

        return $this->belongsTo($userClass, 'uploaded_by');
    }

    /**
     * Get the full URL of the media file.
     */
    public function getFullUrlAttribute(): string
    {
        if ($this->url) {
            return asset($this->url);
        }

        return Storage::disk($this->disk ?? 'public')->url($this->path);
    }

    /**
     * `srcset` of the responsive WebP copies ("…-640w.webp 640w, …"), or null before they exist.
     */
    public function srcset(): ?string
    {
        $conversions = is_array($this->conversions) ? $this->conversions : [];

        if ($conversions === []) {
            return null;
        }

        ksort($conversions);
        $candidates = [];

        foreach ($conversions as $width => $path) {
            if (is_string($path) && (int) $width > 0) {
                $candidates[] = $this->publicUrlFor($path) . ' ' . (int) $width . 'w';
            }
        }

        return $candidates === [] ? null : implode(', ', $candidates);
    }

    private function publicUrlFor(string $path): string
    {
        $url = MediaStoredFile::publicUrl($this->disk ?: 'public', $path);

        return str_starts_with($url, '/') && ! str_starts_with($url, '//') ? asset($url) : $url;
    }

    /**
     * Check if media is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    /**
     * Check if media is a video.
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'video/');
    }

    /**
     * Check if media is audio.
     */
    public function isAudio(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'audio/');
    }

    /**
     * Check if media is a document.
     */
    public function isDocument(): bool
    {
        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument',
            'text/plain',
        ];

        foreach ($documentTypes as $type) {
            if (str_starts_with($this->mime_type ?? '', $type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Lightweight payload for the media picker Alpine details pane.
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     file_name: string,
     *     mime_type: ?string,
     *     url: string,
     *     size: int,
     *     human_size: string,
     *     width: ?int,
     *     height: ?int,
     *     created_at: ?string,
     *     is_image: bool,
     *     is_video: bool,
     *     is_audio: bool
     * }
     */
    public function toPickerCard(): array
    {
        $url = $this->url ?: Storage::disk($this->disk ?? 'public')->url((string) $this->path);

        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'file_name' => (string) $this->file_name,
            'mime_type' => $this->mime_type,
            'url' => $url,
            'size' => (int) ($this->size ?? 0),
            'human_size' => $this->human_size,
            'width' => $this->width,
            'height' => $this->height,
            'created_at' => $this->created_at?->translatedFormat('Y/m/d H:i'),
            'is_image' => $this->isImage(),
            'is_video' => $this->isVideo(),
            'is_audio' => $this->isAudio(),
        ];
    }

    /**
     * Get human-readable file size.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size ?? 0;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Scope: only images.
     */
    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    /**
     * Scope: only videos.
     */
    public function scopeVideos($query)
    {
        return $query->where('mime_type', 'like', 'video/%');
    }

    /**
     * Scope: only audio.
     */
    public function scopeAudios($query)
    {
        return $query->where('mime_type', 'like', 'audio/%');
    }

    /**
     * Scope: only documents.
     */
    public function scopeDocuments($query)
    {
        return $query->where(function ($q) {
            $q->where('mime_type', 'like', 'application/pdf')
                ->orWhere('mime_type', 'like', 'application/msword%')
                ->orWhere('mime_type', 'like', 'application/vnd.openxmlformats%')
                ->orWhere('mime_type', 'like', 'text/%');
        });
    }
}
