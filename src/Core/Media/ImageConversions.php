<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Media;

use GdImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Miran\Mksine\Models\Media;
use Miran\Mksine\Support\MediaStoredFile;
use Throwable;

/**
 * Responsive WebP copies of uploaded raster images, made with GD (no server binaries).
 *
 * For a JPEG/PNG/WebP original W pixels wide, a WebP is written for every configured width
 * below W, plus one at min(W, max width), next to the original
 * (`photo.jpg` → `photo-640w.webp`). The list is stored on {@see Media::$conversions} as
 * width => relative path, and {@see Media::srcset()} turns it into a `srcset`.
 *
 * Animated GIFs and anything GD cannot decode are left alone. Images whose decoded size
 * would not fit in memory_limit are skipped (shared hosts often allow 128 MB; a 24 MP
 * photo needs ~100 MB once decoded).
 */
final class ImageConversions
{
    /** @var list<string> */
    public const array CONVERTIBLE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** @var list<int> */
    public const array DEFAULT_WIDTHS = [320, 640, 960, 1280, 1920];

    public function isEnabled(): bool
    {
        return (bool) config('mksine.media.optimize_images', true)
            && function_exists('imagewebp')
            && function_exists('imagecreatefromstring');
    }

    public function canConvert(Media $media): bool
    {
        return $this->isEnabled()
            && $this->hasConversionsColumn()
            && in_array(strtolower((string) $media->mime_type), self::CONVERTIBLE_MIME_TYPES, true)
            && is_string($media->path) && $media->path !== '';
    }

    /**
     * Sites that have not run the conversions migration yet keep working without copies.
     */
    private function hasConversionsColumn(): bool
    {
        static $exists = false;

        if ($exists) {
            return true;
        }

        try {
            return $exists = Schema::hasColumn('media', 'conversions');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return list<int>
     */
    public function widths(): array
    {
        $widths = array_map('intval', (array) (config('mksine.media.responsive_widths') ?? self::DEFAULT_WIDTHS));
        $widths = array_values(array_unique(array_filter($widths, static fn (int $width): bool => $width >= 16 && $width <= 8192)));
        sort($widths);

        return $widths;
    }

    /**
     * Write the WebP copies and store their paths on the media row. Returns the new map.
     *
     * @return array<int, string>
     */
    public function generate(Media $media): array
    {
        if (! $this->canConvert($media)) {
            return [];
        }

        $disk = Storage::disk($media->disk ?: (string) config('mksine.media.disk', 'public'));
        $path = (string) $media->path;

        try {
            if (! MediaStoredFile::isSafeRelativePath($path) || ! $disk->exists($path)) {
                return [];
            }

            $bytes = (string) $disk->get($path);
            $size = @getimagesizefromstring($bytes);

            if (! is_array($size) || ! $this->fitsInMemory((int) $size[0], (int) $size[1])) {
                return [];
            }

            $image = @imagecreatefromstring($bytes);
            unset($bytes);

            if (! $image instanceof GdImage) {
                return [];
            }

        } catch (Throwable $exception) {
            Log::warning('Media conversion skipped: could not read image', ['media_id' => $media->getKey(), 'error' => $exception->getMessage()]);

            return [];
        }

        try {
            $image = $this->applyExifOrientation($image, $disk->path($path), (string) $media->mime_type);
        } catch (Throwable) {
            // Disks without local paths (S3): keep the stored orientation.
        }

        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        $quality = max(30, min(100, (int) config('mksine.media.webp_quality', 80)));
        $targets = array_filter($this->widths(), static fn (int $width): bool => $width < $originalWidth);
        $targets[] = min($originalWidth, max($this->widths() ?: [$originalWidth]));

        $conversions = [];

        foreach (array_unique($targets) as $width) {
            $height = max(1, (int) round($originalHeight * $width / $originalWidth));
            $resized = $this->resize($image, $width, $height);

            ob_start();
            $written = imagewebp($resized, null, $quality);
            $webp = (string) ob_get_clean();

            if ($resized !== $image) {
                imagedestroy($resized);
            }

            if (! $written || $webp === '') {
                continue;
            }

            $target = $this->variantPath($path, $width);

            try {
                $disk->put($target, $webp);
                $conversions[$width] = $target;
            } catch (Throwable $exception) {
                Log::warning('Media conversion could not be written', ['media_id' => $media->getKey(), 'path' => $target, 'error' => $exception->getMessage()]);
            }
        }

        imagedestroy($image);
        ksort($conversions);

        $media->forceFill(['conversions' => $conversions === [] ? null : $conversions])->saveQuietly();

        return $conversions;
    }

    /**
     * Remove the WebP copies of a media item (original untouched).
     *
     * @param  array<int|string, string>|null  $conversions
     */
    public function delete(Media $media, ?array $conversions = null): void
    {
        $conversions ??= is_array($media->conversions) ? $media->conversions : [];

        if ($conversions === []) {
            return;
        }

        try {
            $disk = Storage::disk($media->disk ?: (string) config('mksine.media.disk', 'public'));

            foreach ($conversions as $path) {
                if (is_string($path) && MediaStoredFile::isSafeRelativePath($path) && str_ends_with($path, 'w.webp')) {
                    $disk->delete($path);
                }
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function variantPath(string $originalPath, int $width): string
    {
        $info = pathinfo($originalPath);
        $directory = ($info['dirname'] ?? '.') === '.' ? '' : $info['dirname'] . '/';

        return $directory . $info['filename'] . '-' . $width . 'w.webp';
    }

    private function resize(GdImage $image, int $width, int $height): GdImage
    {
        if ($width === imagesx($image) && $height === imagesy($image) && imageistruecolor($image)) {
            return $image;
        }

        $resized = imagecreatetruecolor($width, $height);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));

        return $resized;
    }

    /**
     * Phone photos store rotation in EXIF; GD ignores it, so copies would come out sideways.
     */
    private function applyExifOrientation(GdImage $image, string $absolutePath, string $mime): GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data') || ! is_file($absolutePath)) {
            return $image;
        }

        $exif = @exif_read_data($absolutePath);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        if ($rotated instanceof GdImage && $rotated !== $image) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }

    private function fitsInMemory(int $width, int $height): bool
    {
        if ($width < 1 || $height < 1) {
            return false;
        }

        $maxPixels = (int) config('mksine.media.max_conversion_pixels', 40_000_000);

        if ($width * $height > $maxPixels) {
            return false;
        }

        $limit = $this->memoryLimitBytes();

        if ($limit === null) {
            return true;
        }

        // Decoded truecolor image (~5 bytes/pixel with GD overhead) plus the largest copy.
        $needed = (int) ($width * $height * 5 * 1.6);

        return memory_get_usage(true) + $needed < $limit;
    }

    private function memoryLimitBytes(): ?int
    {
        $raw = trim((string) ini_get('memory_limit'));

        if ($raw === '' || $raw === '-1') {
            return null;
        }

        $value = (int) $raw;

        return match (strtolower(substr($raw, -1))) {
            'g' => $value * 1024 ** 3,
            'm' => $value * 1024 ** 2,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
