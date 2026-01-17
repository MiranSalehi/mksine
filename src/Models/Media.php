<?php

declare(strict_types=1);

namespace Miran\Mksine\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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
    ];

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
