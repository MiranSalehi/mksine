<?php

namespace Miran\Mksine\Filament\Resources\Media\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Miran\Mksine\Filament\Resources\Media\MediaResource;

class CreateMedia extends CreateRecord
{
    protected static string $resource = MediaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Handle file upload - Filament handles the upload, we just extract metadata
        if (isset($data['file'])) {
            $disk = $data['disk'] ?? 'public';

            // Handle both array (multiple) and string (single) states
            $fileRelativePath = is_array($data['file'])
                ? (count($data['file']) > 0 ? $data['file'][0] : null)
                : $data['file'];

            if ($fileRelativePath) {
                try {
                    $filePath = Storage::disk($disk)->path($fileRelativePath);

                    if (file_exists($filePath)) {
                        $fileInfo = pathinfo($filePath);
                        $fileName = $fileInfo['basename'];

                        // Ensure all required fields are set
                        $data['name'] = $data['name'] ?? $fileInfo['filename'];
                        $data['file_name'] = $data['file_name'] ?? $fileName;
                        $data['mime_type'] = $data['mime_type'] ?? mime_content_type($filePath);
                        $data['size'] = $data['size'] ?? filesize($filePath);
                        $data['path'] = $data['path'] ?? $fileRelativePath;
                        $data['disk'] = $disk;

                        // Generate URL based on disk configuration
                        $diskConfig = config("filesystems.disks.{$disk}", []);
                        $diskUrl = $diskConfig['url'] ?? null;

                        if ($diskUrl) {
                            // Use URL from filesystem config
                            $data['url'] = rtrim($diskUrl, '/') . '/' . $fileRelativePath;
                        } else {
                            // Try to get URL from Storage facade
                            try {
                                /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
                                $storage = Storage::disk($disk);
                                if (method_exists($storage, 'url')) {
                                    $data['url'] = $storage->url($fileRelativePath);
                                } else {
                                    $data['url'] = $fileRelativePath;
                                }
                            } catch (\Exception $e) {
                                $data['url'] = $fileRelativePath;
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
}
