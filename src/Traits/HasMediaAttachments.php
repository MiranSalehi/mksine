<?php

declare(strict_types=1);

namespace Miran\Mksine\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Miran\Mksine\Models\Media;
use Miran\Mksine\Models\MediaAttachment;

trait HasMediaAttachments
{
    /**
     * Get all media attachments for this model.
     */
    public function mediaAttachments(): MorphMany
    {
        return $this->morphMany(MediaAttachment::class, 'mediable');
    }

    /**
     * Get media attachments for a specific collection.
     */
    public function getMediaCollection(string $collectionName): Collection
    {
        return $this->mediaAttachments()
            ->where('collection_name', $collectionName)
            ->with('media')
            ->get()
            ->pluck('media');
    }

    /**
     * Get the first media item from a collection.
     */
    public function getFirstMedia(string $collectionName): ?Media
    {
        return $this->getMediaCollection($collectionName)->first();
    }

    /**
     * Get the URL of the first media item from a collection.
     */
    public function getFirstMediaUrl(string $collectionName): ?string
    {
        $media = $this->getFirstMedia($collectionName);

        if (! $media) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk($media->disk)->url($media->path);
    }

    /**
     * Check if the model has any media in a collection.
     */
    public function hasMedia(string $collectionName): bool
    {
        return $this->mediaAttachments()
            ->where('collection_name', $collectionName)
            ->exists();
    }

    /**
     * Attach media to a collection.
     */
    public function attachMedia(int | array $mediaIds, string $collectionName, bool $clearExisting = false): void
    {
        if ($clearExisting) {
            $this->clearMediaCollection($collectionName);
        }

        $mediaIds = is_array($mediaIds) ? $mediaIds : [$mediaIds];

        foreach ($mediaIds as $mediaId) {
            MediaAttachment::create([
                'media_id' => $mediaId,
                'mediable_type' => get_class($this),
                'mediable_id' => $this->getKey(),
                'collection_name' => $collectionName,
            ]);
        }
    }

    /**
     * Sync media in a collection.
     */
    public function syncMedia(array $mediaIds, string $collectionName): void
    {
        $this->attachMedia($mediaIds, $collectionName, clearExisting: true);
    }

    /**
     * Clear all media from a collection.
     */
    public function clearMediaCollection(string $collectionName): void
    {
        $this->mediaAttachments()
            ->where('collection_name', $collectionName)
            ->delete();
    }

    /**
     * Detach specific media from a collection.
     */
    public function detachMedia(int $mediaId, ?string $collectionName = null): void
    {
        $query = $this->mediaAttachments()->where('media_id', $mediaId);

        if ($collectionName) {
            $query->where('collection_name', $collectionName);
        }

        $query->delete();
    }
}
