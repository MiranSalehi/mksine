<?php

namespace Miran\Mksine\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Miran\Mksine\Models\Media;
use Miran\Mksine\Models\MediaAttachment;

trait HasMedia
{
    /**
     * Get all media attachments for this model.
     */
    public function mediaAttachments(): MorphMany
    {
        return $this->morphMany(MediaAttachment::class, 'mediable');
    }

    /**
     * Get media for a specific collection.
     */
    public function getMedia(?string $collectionName = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->mediaAttachments()->with('media');

        if ($collectionName) {
            $query->where('collection_name', $collectionName);
        }

        return $query->get()->pluck('media');
    }

    /**
     * Attach a media to this model.
     */
    public function attachMedia(int $mediaId, ?string $collectionName = null, ?string $alt = null): MediaAttachment
    {
        return $this->mediaAttachments()->create([
            'media_id' => $mediaId,
            'collection_name' => $collectionName,
            'alt' => $alt,
        ]);
    }

    /**
     * Detach a media from this model.
     */
    public function detachMedia(int $mediaId, ?string $collectionName = null): bool
    {
        $query = $this->mediaAttachments()->where('media_id', $mediaId);

        if ($collectionName) {
            $query->where('collection_name', $collectionName);
        }

        return $query->delete();
    }

    /**
     * Sync media for this model (detach all and attach new ones).
     */
    public function syncMedia(array $mediaIds, ?string $collectionName = null): void
    {
        $this->mediaAttachments()
            ->when($collectionName, fn ($query) => $query->where('collection_name', $collectionName))
            ->delete();

        foreach ($mediaIds as $mediaId) {
            $this->attachMedia($mediaId, $collectionName);
        }
    }
}
