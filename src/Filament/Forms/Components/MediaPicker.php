<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Miran\Mksine\Models\Media;
use Miran\Mksine\Models\MediaAttachment;

class MediaPicker extends Field
{
    protected string $view = 'mksine::filament.forms.components.media-picker';

    protected bool | Closure $isMultiple = false;

    protected bool | Closure $isRelation = true;

    protected string | Closure | null $collection = null;

    protected array | Closure $acceptedFileTypes = ['image/*'];

    protected int | Closure | null $maxItems = null;

    protected int | Closure | null $minItems = null;

    protected ?Closure $authorizationCallback = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(function (MediaPicker $component, $state, ?Model $record): void {
            if (! $record) {
                return;
            }

            if ($this->evaluate($this->isRelation)) {
                $this->hydrateFromRelation($component, $record);
            } else {
                $this->hydrateFromColumn($component, $state);
            }
        });

        // Validation before dehydration
        $this->beforeStateDehydrated(function (MediaPicker $component, $state): void {
            $this->performValidation($state);
        });

        $this->dehydrateStateUsing(function (MediaPicker $component, $state): mixed {
            // State is already an array of media IDs
            if ($this->evaluate($this->isRelation)) {
                // Return null for relation, we handle it in saveRelationships
                return null;
            }

            // Sanitize IDs before saving
            $sanitizedState = $this->sanitizeMediaIds($state);

            if ($this->evaluate($this->isMultiple)) {
                return is_array($sanitizedState) ? $sanitizedState : [];
            }

            return is_array($sanitizedState) ? ($sanitizedState[0] ?? null) : $sanitizedState;
        });

        $this->saveRelationshipsUsing(function (MediaPicker $component, ?Model $record, $state): void {
            if (! $record || ! $this->evaluate($this->isRelation)) {
                return;
            }

            // Sanitize IDs before saving
            $sanitizedState = $this->sanitizeMediaIds($state);

            $this->saveToRelation($record, $sanitizedState);
        });
    }

    /**
     * Perform validation on the media picker state.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function performValidation($state): void
    {
        if (empty($state)) {
            return;
        }

        $ids = is_array($state) ? $state : [$state];

        // Filter out empty values
        $ids = array_filter($ids, fn ($id) => ! empty($id));

        if (empty($ids)) {
            return;
        }

        // Validate IDs are integers
        foreach ($ids as $id) {
            if (! is_numeric($id) || (int) $id <= 0) {
                $this->throwValidationError(__('Invalid media ID provided.'));
            }
        }

        // Validate media exists
        $validatedIds = $this->validateMediaExists($ids);
        if (count($validatedIds) !== count($ids)) {
            $this->throwValidationError(__('One or more selected media items do not exist.'));
        }

        // Authorization check
        if (! $this->authorizeMediaAccess($ids)) {
            $this->throwValidationError(__('You do not have permission to use one or more selected media items.'));
        }

        // Max items validation
        $maxItems = $this->evaluate($this->maxItems);
        if ($maxItems !== null && count($ids) > $maxItems) {
            $this->throwValidationError(__('You can select a maximum of :max items.', ['max' => $maxItems]));
        }

        // Min items validation
        $minItems = $this->evaluate($this->minItems);
        if ($minItems !== null && count($ids) < $minItems) {
            $this->throwValidationError(__('You must select at least :min items.', ['min' => $minItems]));
        }

        // Validate mime types
        if (! $this->validateMimeTypes($ids)) {
            $this->throwValidationError(__('One or more selected media items have invalid file types.'));
        }
    }

    /**
     * Throw a validation error.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwValidationError(string $message): void
    {
        throw \Illuminate\Validation\ValidationException::withMessages([
            $this->getStatePath() => $message,
        ]);
    }

    /**
     * Set maximum number of items that can be selected.
     */
    public function maxItems(int | Closure | null $max): static
    {
        $this->maxItems = $max;

        return $this;
    }

    /**
     * Set minimum number of items that must be selected.
     */
    public function minItems(int | Closure | null $min): static
    {
        $this->minItems = $min;

        return $this;
    }

    /**
     * Set authorization callback for media access.
     * Callback receives array of media IDs and should return bool.
     */
    public function authorize(Closure $callback): static
    {
        $this->authorizationCallback = $callback;

        return $this;
    }

    public function multiple(bool | Closure $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function isRelation(bool | Closure $condition = true): static
    {
        $this->isRelation = $condition;

        return $this;
    }

    public function collection(string | Closure | null $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    public function acceptedFileTypes(array | Closure $types): static
    {
        $this->acceptedFileTypes = $types;

        return $this;
    }

    public function getIsMultiple(): bool
    {
        return (bool) $this->evaluate($this->isMultiple);
    }

    public function getIsRelation(): bool
    {
        return (bool) $this->evaluate($this->isRelation);
    }

    public function getCollection(): ?string
    {
        return $this->evaluate($this->collection);
    }

    public function getAcceptedFileTypes(): array
    {
        return (array) $this->evaluate($this->acceptedFileTypes);
    }

    /**
     * Get selected media items for display.
     */
    public function getSelectedMedia(): Collection
    {
        $state = $this->getState();

        if (empty($state)) {
            return collect();
        }

        $ids = is_array($state) ? $state : [$state];

        return Media::whereIn('id', $ids)->get();
    }

    protected function hydrateFromRelation(MediaPicker $component, Model $record): void
    {
        $collection = $this->getCollection() ?? $this->getName();

        $mediaIds = MediaAttachment::where('mediable_type', get_class($record))
            ->where('mediable_id', $record->getKey())
            ->where('collection_name', $collection)
            ->pluck('media_id')
            ->toArray();

        $component->state($mediaIds);
    }

    protected function hydrateFromColumn(MediaPicker $component, $state): void
    {
        if (is_null($state)) {
            $component->state([]);

            return;
        }

        // If it's a JSON string, decode it
        if (is_string($state)) {
            $decoded = json_decode($state, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $component->state(is_array($decoded) ? $decoded : [$decoded]);

                return;
            }
            // If not JSON, treat as single ID
            $component->state([(int) $state]);

            return;
        }

        // If it's already an array
        if (is_array($state)) {
            $component->state($state);

            return;
        }

        // Single value
        $component->state([$state]);
    }

    protected function saveToRelation(Model $record, $state): void
    {
        $collection = $this->getCollection() ?? $this->getName();
        $mediaIds = is_array($state) ? array_filter($state) : [];

        // Delete existing attachments for this collection
        MediaAttachment::where('mediable_type', get_class($record))
            ->where('mediable_id', $record->getKey())
            ->where('collection_name', $collection)
            ->delete();

        // Create new attachments
        foreach ($mediaIds as $index => $mediaId) {
            MediaAttachment::create([
                'media_id' => $mediaId,
                'mediable_type' => get_class($record),
                'mediable_id' => $record->getKey(),
                'collection_name' => $collection,
            ]);
        }
    }

    /**
     * Sanitize media IDs to ensure they are valid integers.
     */
    protected function sanitizeMediaIds($state): array
    {
        if (empty($state)) {
            return [];
        }

        $ids = is_array($state) ? $state : [$state];

        return array_values(array_filter(array_map(function ($id) {
            if (is_numeric($id) && (int) $id > 0) {
                return (int) $id;
            }

            return null;
        }, $ids)));
    }

    /**
     * Validate that all media IDs exist in database.
     */
    protected function validateMediaExists(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        try {
            return Media::whereIn('id', $ids)->pluck('id')->toArray();
        } catch (\Exception $e) {
            Log::warning('MediaPicker: Failed to validate media existence', [
                'ids' => $ids,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Check authorization for media access.
     */
    protected function authorizeMediaAccess(array $ids): bool
    {
        // If custom authorization callback is set, use it
        if ($this->authorizationCallback) {
            try {
                return (bool) $this->evaluate($this->authorizationCallback, [
                    'ids' => $ids,
                ]);
            } catch (\Exception $e) {
                Log::error('MediaPicker: Authorization callback failed', [
                    'ids' => $ids,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        }

        // Default: allow access (for backwards compatibility)
        // In production, you might want to implement proper authorization
        return true;
    }

    /**
     * Validate mime types of selected media.
     */
    protected function validateMimeTypes(array $ids): bool
    {
        $acceptedTypes = $this->getAcceptedFileTypes();

        // If accepting all types, skip validation
        if (in_array('*', $acceptedTypes) || in_array('*/*', $acceptedTypes)) {
            return true;
        }

        try {
            $media = Media::whereIn('id', $ids)->get(['id', 'mime_type']);

            foreach ($media as $item) {
                if (! $this->isMimeTypeAccepted($item->mime_type, $acceptedTypes)) {
                    Log::debug('MediaPicker: Invalid mime type detected', [
                        'media_id' => $item->id,
                        'mime_type' => $item->mime_type,
                        'accepted_types' => $acceptedTypes,
                    ]);

                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::warning('MediaPicker: Failed to validate mime types', [
                'ids' => $ids,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if a mime type is accepted.
     */
    protected function isMimeTypeAccepted(string $mimeType, array $acceptedTypes): bool
    {
        foreach ($acceptedTypes as $accepted) {
            // Exact match
            if ($accepted === $mimeType) {
                return true;
            }

            // Wildcard match (e.g., 'image/*')
            if (str_ends_with($accepted, '/*')) {
                $prefix = substr($accepted, 0, -1); // 'image/'
                if (str_starts_with($mimeType, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get maximum items allowed.
     */
    public function getMaxItems(): ?int
    {
        return $this->evaluate($this->maxItems);
    }

    /**
     * Get minimum items required.
     */
    public function getMinItems(): ?int
    {
        return $this->evaluate($this->minItems);
    }
}
