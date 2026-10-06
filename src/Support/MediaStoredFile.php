<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * File metadata that must be read from storage, never from form state.
 *
 * The Media edit form used to mark these attributes as disabled and then call
 * `dehydrated()`, which puts whatever the client sent back into the save payload.
 * Combined with Media::$fillable, that let anyone with Update:Media relabel an
 * SVG as image/png and walk it past the picker mime filter.
 */
final class MediaStoredFile
{
    /**
     * @var list<string>
     */
    public const DERIVED_KEYS = [
        'file_name',
        'mime_type',
        'size',
        'width',
        'height',
        'path',
        'url',
    ];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function forgetDerived(array $data): array
    {
        foreach (self::DERIVED_KEYS as $key) {
            unset($data[$key]);
        }

        return $data;
    }

    /**
     * Overlay attributes taken from the file already stored on {@see $data}'s disk.
     *
     * {@see $originalFileName} is used only when the stored path is new (create or replace).
     * It is treated as a display label: path separators and NULs are rejected so it cannot
     * become a second path field.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function apply(array $data, ?string $existingPath = null, mixed $originalFileName = null): array
    {
        $disk = self::diskName($data['disk'] ?? null);
        $data['disk'] = $disk;

        $relative = self::relativePath($data['file'] ?? null);

        if ($relative === null) {
            return $data;
        }

        if (! self::isSafeRelativePath($relative)) {
            throw ValidationException::withMessages([
                'file' => __('mksine::media.invalid_file_path'),
            ]);
        }

        $storage = Storage::disk($disk);

        try {
            if (! $storage->exists($relative)) {
                return $data;
            }
        } catch (\Throwable) {
            return $data;
        }

        $replaced = $existingPath === null || $existingPath !== $relative;

        $data['path'] = $relative;
        $data['url'] = self::publicUrl($disk, $relative);
        $data['mime_type'] = self::detectMime($storage, $relative) ?? 'application/octet-stream';
        $data['size'] = self::detectSize($storage, $relative);
        $data['width'] = null;
        $data['height'] = null;

        if ($replaced) {
            $data['file_name'] = self::safeFileName($originalFileName) ?? basename($relative);
        }

        if (
            (! isset($data['name']) || ! is_string($data['name']) || trim($data['name']) === '')
            && $replaced
        ) {
            $data['name'] = pathinfo((string) $data['file_name'], PATHINFO_FILENAME);
        }

        $dimensions = self::detectDimensions($storage, $relative, $data['mime_type']);
        $data['width'] = $dimensions['width'];
        $data['height'] = $dimensions['height'];

        return $data;
    }

    public static function isSafeRelativePath(string $relative): bool
    {
        $normalized = str_replace('\\', '/', $relative);

        if ($normalized === '' || str_contains($normalized, "\0") || str_contains($normalized, '://')) {
            return false;
        }

        if (str_starts_with($normalized, '/') || str_contains($normalized, ':')) {
            return false;
        }

        $segments = explode('/', $normalized);

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return str_starts_with($normalized, MediaStoragePath::PREFIX.'/');
    }

    public static function publicUrl(string $disk, string $relative): string
    {
        $diskUrl = config("filesystems.disks.{$disk}.url");

        if (is_string($diskUrl) && $diskUrl !== '') {
            return rtrim($diskUrl, '/').'/'.ltrim($relative, '/');
        }

        try {
            $storage = Storage::disk($disk);

            if (method_exists($storage, 'url')) {
                return $storage->url($relative);
            }
        } catch (\Throwable) {
            // Fall through to the relative path; some drivers have no public URL.
        }

        return $relative;
    }

    private static function diskName(mixed $disk): string
    {
        if (is_string($disk) && $disk !== '' && array_key_exists($disk, config('filesystems.disks', []))) {
            return $disk;
        }

        $configured = config('mksine.media.disk', 'public');

        return is_string($configured) && $configured !== '' ? $configured : 'public';
    }

    private static function relativePath(mixed $file): ?string
    {
        if (is_array($file)) {
            $file = $file[0] ?? null;
        }

        if (! is_string($file) || $file === '') {
            return null;
        }

        $relative = ltrim(str_replace('\\', '/', $file), '/');

        if (! str_starts_with($relative, MediaStoragePath::PREFIX.'/')) {
            $relative = MediaStoragePath::PREFIX.'/'.$relative;
        }

        return $relative;
    }

    private static function safeFileName(mixed $name): ?string
    {
        if (! is_string($name)) {
            return null;
        }

        $name = basename(str_replace('\\', '/', $name));

        if ($name === '' || $name === '.' || $name === '..' || str_contains($name, "\0") || strlen($name) > 255) {
            return null;
        }

        return $name;
    }

    private static function detectMime(FilesystemAdapter $storage, string $relative): ?string
    {
        $absolute = self::containedAbsolutePath($storage, $relative);

        if ($absolute !== null) {
            $detected = mime_content_type($absolute);

            if (is_string($detected) && $detected !== '') {
                return $detected;
            }
        }

        try {
            $mime = $storage->mimeType($relative);
        } catch (\Throwable) {
            return null;
        }

        return is_string($mime) && $mime !== '' ? $mime : null;
    }

    private static function detectSize(FilesystemAdapter $storage, string $relative): int
    {
        $absolute = self::containedAbsolutePath($storage, $relative);

        if ($absolute !== null) {
            $bytes = filesize($absolute);

            if (is_int($bytes) && $bytes >= 0) {
                return $bytes;
            }
        }

        try {
            return $storage->size($relative);
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return array{width: int|null, height: int|null}
     */
    private static function detectDimensions(FilesystemAdapter $storage, string $relative, string $mime): array
    {
        if (! str_starts_with($mime, 'image/')) {
            return ['width' => null, 'height' => null];
        }

        $absolute = self::containedAbsolutePath($storage, $relative);

        if ($absolute === null) {
            return ['width' => null, 'height' => null];
        }

        $info = @getimagesize($absolute);

        if (! is_array($info)) {
            return ['width' => null, 'height' => null];
        }

        return [
            'width' => $info[0],
            'height' => $info[1],
        ];
    }

    /**
     * Local path only when it resolves inside the disk's media prefix. Remote disks
     * have no {@see FilesystemAdapter::path()}; callers fall back to driver metadata.
     */
    private static function containedAbsolutePath(FilesystemAdapter $storage, string $relative): ?string
    {
        try {
            $absolute = $storage->path($relative);
            $mediaRoot = $storage->path(MediaStoragePath::PREFIX);
        } catch (\Throwable) {
            return null;
        }

        if (! is_file($absolute)) {
            return null;
        }

        return FilesystemPath::isWithin($mediaRoot, $absolute) ? $absolute : null;
    }
}
