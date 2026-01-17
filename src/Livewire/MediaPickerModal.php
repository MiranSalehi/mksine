<?php

declare(strict_types=1);

namespace Miran\Mksine\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Miran\Mksine\Models\Media;

class MediaPickerModal extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;

    public string $statePath = '';

    public bool $multiple = false;

    public array $acceptedFileTypes = ['image/*'];

    public array $selectedIds = [];

    public string $search = '';

    public string $typeFilter = '';

    public $uploadedFiles = [];

    protected $listeners = [
        'openMediaPicker' => 'open',
    ];

    public function open(string $statePath, bool $multiple = false, array $acceptedFileTypes = ['image/*'], array $currentSelection = []): void
    {
        $this->statePath = $statePath;
        $this->multiple = $multiple;
        $this->acceptedFileTypes = $acceptedFileTypes;
        $this->selectedIds = $currentSelection;
        $this->search = '';
        $this->typeFilter = '';
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->reset(['uploadedFiles', 'search', 'typeFilter']);
    }

    public function toggleSelection(int $mediaId): void
    {
        if ($this->multiple) {
            if (in_array($mediaId, $this->selectedIds)) {
                $this->selectedIds = array_values(array_diff($this->selectedIds, [$mediaId]));
            } else {
                $this->selectedIds[] = $mediaId;
            }
        } else {
            $this->selectedIds = [$mediaId];
        }
    }

    public function isSelected(int $mediaId): bool
    {
        return in_array($mediaId, $this->selectedIds);
    }

    public function confirm(): void
    {
        // Get full media data for selected IDs
        $selectedMedia = Media::whereIn('id', $this->selectedIds)->get()->toArray();

        $this->dispatch(
            'media-selected',
            statePath: $this->statePath,
            selectedIds: $this->selectedIds,
            selectedMedia: $selectedMedia
        );

        $this->close();
    }

    public function uploadFiles(): void
    {
        if (empty($this->uploadedFiles)) {
            return;
        }

        $disk = 'public';

        foreach ($this->uploadedFiles as $file) {
            // Store file in media directory (same as MediaResource)
            $path = $file->store('media', $disk);

            // Generate URL
            $url = Storage::disk($disk)->url($path);

            $media = Media::create([
                'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'path' => $path,
                'disk' => $disk,
                'size' => $file->getSize(),
                'width' => $this->getImageWidth($file),
                'height' => $this->getImageHeight($file),
                'url' => $url,
            ]);

            if ($this->multiple) {
                $this->selectedIds[] = $media->id;
            } else {
                $this->selectedIds = [$media->id];
            }
        }

        $this->uploadedFiles = [];
    }

    protected function getImageWidth($file): ?int
    {
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $imageInfo = getimagesize($file->getRealPath());

            return $imageInfo[0] ?? null;
        }

        return null;
    }

    protected function getImageHeight($file): ?int
    {
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $imageInfo = getimagesize($file->getRealPath());

            return $imageInfo[1] ?? null;
        }

        return null;
    }

    public function getMediaProperty()
    {
        $query = Media::query()
            ->orderBy('created_at', 'desc');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('file_name', 'like', "%{$this->search}%");
            });
        }

        if ($this->typeFilter) {
            $query->where('mime_type', 'like', "{$this->typeFilter}%");
        }

        return $query->paginate(24);
    }

    public function getFileTypes(): array
    {
        return [
            '' => __('All Types'),
            'image/' => __('Images'),
            'video/' => __('Videos'),
            'application/pdf' => __('PDF'),
            'application/' => __('Documents'),
        ];
    }

    public function render(): View
    {
        return view('mksine::livewire.media-picker-modal', [
            'mediaItems' => $this->getMediaProperty(),
            'fileTypes' => $this->getFileTypes(),
        ]);
    }
}
