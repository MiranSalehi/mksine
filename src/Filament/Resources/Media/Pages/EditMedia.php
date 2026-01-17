<?php

namespace Miran\Mksine\Filament\Resources\Media\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;
use Miran\Mksine\Filament\Resources\Media\MediaResource;

class EditMedia extends EditRecord
{
    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Set the file path for FileUpload to display existing file
        // FileUpload needs the full path relative to the disk root (including 'media/' prefix)
        if (isset($data['path']) && ! empty($data['path'])) {
            // Use path as is (with 'media/' prefix) since FileUpload will handle it
            $data['file'] = $data['path'];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle file upload if new file is uploaded - Filament handles the upload, we just extract metadata
        if (isset($data['file'])) {
            $disk = $data['disk'] ?? 'public';

            // Handle both array (multiple) and string (single) states
            $fileRelativePath = is_array($data['file'])
                ? (count($data['file']) > 0 ? $data['file'][0] : null)
                : $data['file'];

            if ($fileRelativePath) {
                try {
                    // Ensure path includes 'media/' prefix if it doesn't already
                    $fullPath = str_starts_with($fileRelativePath, 'media/')
                        ? $fileRelativePath
                        : 'media/' . $fileRelativePath;

                    $filePath = Storage::disk($disk)->path($fullPath);

                    if (file_exists($filePath)) {
                        $fileInfo = pathinfo($filePath);
                        $fileName = $fileInfo['basename'];

                        $data['name'] = $data['name'] ?? $fileInfo['filename'];
                        $data['file_name'] = $data['file_name'] ?? $fileName;
                        $data['mime_type'] = $data['mime_type'] ?? mime_content_type($filePath);
                        $data['size'] = $data['size'] ?? filesize($filePath);
                        // Always update path with the new file path (with 'media/' prefix)
                        $data['path'] = $fullPath;

                        // Generate URL based on disk configuration
                        $diskConfig = config("filesystems.disks.{$disk}", []);
                        $diskUrl = $diskConfig['url'] ?? null;

                        if ($diskUrl) {
                            // Use URL from filesystem config
                            $data['url'] = rtrim($diskUrl, '/') . '/' . $fullPath;
                        } else {
                            // Try to get URL from Storage facade
                            try {
                                /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
                                $storage = Storage::disk($disk);
                                if (method_exists($storage, 'url')) {
                                    $data['url'] = $storage->url($fullPath);
                                } else {
                                    $data['url'] = $fullPath;
                                }
                            } catch (\Exception $e) {
                                $data['url'] = $fullPath;
                            }
                        }

                        // Get image dimensions if it's an image
                        if (str_starts_with($data['mime_type'], 'image/')) {
                            $imageInfo = getimagesize($filePath);
                            if ($imageInfo) {
                                $data['width'] = $imageInfo[0];
                                $data['height'] = $imageInfo[1];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // If path() doesn't work (e.g., cloud storage), skip file metadata extraction
                }
            }
        }

        // Remove file from data as it's not a database column
        unset($data['file']);

        return $data;
    }

    protected function afterSave(): void
    {
        // Refresh the record to get the updated path
        $this->record->refresh();

        // Update the form's file field with the new path (full path with 'media/' prefix)
        $this->form->fill([
            'file' => $this->record->path,
        ]);
    }
}
