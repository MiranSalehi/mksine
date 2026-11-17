<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Miran\Mksine\Models\Media;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    if (! function_exists('imagewebp')) {
        $this->markTestSkipped('GD without WebP support.');
    }

    if (! Schema::hasColumn('media', 'conversions')) {
        (require dirname(__DIR__, 2) . '/database/migrations/2026_11_17_120000_add_conversions_to_media_table.php')->up();
    }

    Storage::fake('public');
    config(['mksine.media.optimize_images' => true, 'mksine.media.responsive_widths' => [320, 640, 960, 1280, 1920]]);
});

function conversionImage(int $width, int $height, string $type = 'jpeg'): string
{
    $image = imagecreatetruecolor($width, $height);

    if ($type === 'png') {
        imagesavealpha($image, true);
        imagealphablending($image, false);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagefilledrectangle($image, 0, 0, intdiv($width, 2), $height, imagecolorallocate($image, 200, 30, 30));
    } else {
        imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocate($image, 30, 120, 200));
    }

    ob_start();
    match ($type) {
        'png' => imagepng($image),
        'gif' => imagegif($image),
        default => imagejpeg($image, null, 85),
    };

    return (string) ob_get_clean();
}

function conversionMedia(string $path, string $mime, string $bytes): Media
{
    Storage::disk('public')->put($path, $bytes);
    $size = getimagesizefromstring($bytes);

    return Media::query()->create([
        'name' => basename($path),
        'file_name' => basename($path),
        'mime_type' => $mime,
        'path' => $path,
        'disk' => 'public',
        'size' => strlen($bytes),
        'width' => $size[0] ?? null,
        'height' => $size[1] ?? null,
        'url' => '/uploads/' . $path,
    ]);
}

test('an uploaded photo gets webp copies at every smaller width', function (): void {
    $media = conversionMedia('media/2026/11/photo.jpg', 'image/jpeg', conversionImage(2000, 1000));

    $conversions = $media->fresh()->conversions;

    expect(array_map('intval', array_keys($conversions)))->toBe([320, 640, 960, 1280, 1920])
        ->and($conversions[640])->toBe('media/2026/11/photo-640w.webp');

    $copy = getimagesizefromstring(Storage::disk('public')->get($conversions[640]));

    expect($copy[0])->toBe(640)
        ->and($copy[1])->toBe(320)
        ->and($copy['mime'])->toBe('image/webp');
});

test('small images get one webp copy at their own width', function (): void {
    $media = conversionMedia('media/small.png', 'image/png', conversionImage(300, 200, 'png'));

    expect(array_keys($media->fresh()->conversions))->toBe([300]);
});

test('transparency survives the conversion', function (): void {
    $media = conversionMedia('media/logo.png', 'image/png', conversionImage(800, 400, 'png'));
    $copy = imagecreatefromstring(Storage::disk('public')->get($media->fresh()->conversions[640]));

    $alpha = (imagecolorat($copy, 600, 200) >> 24) & 0x7F;

    expect($alpha)->toBe(127);
});

test('gifs, oversized images and disabled conversions are left alone', function (): void {
    expect(conversionMedia('media/anim.gif', 'image/gif', conversionImage(400, 400, 'gif'))->fresh()->conversions)->toBeNull();

    config(['mksine.media.max_conversion_pixels' => 1000]);
    expect(conversionMedia('media/huge.jpg', 'image/jpeg', conversionImage(800, 800))->fresh()->conversions)->toBeNull();

    config(['mksine.media.max_conversion_pixels' => 40_000_000, 'mksine.media.optimize_images' => false]);
    expect(conversionMedia('media/off.jpg', 'image/jpeg', conversionImage(800, 800))->fresh()->conversions)->toBeNull();
});

test('replacing the file rebuilds the copies and permanent deletion removes them', function (): void {
    $media = conversionMedia('media/first.jpg', 'image/jpeg', conversionImage(1000, 500));
    $oldCopy = $media->fresh()->conversions[640];

    Storage::disk('public')->put('media/second.jpg', conversionImage(700, 700));
    $media->fresh()->update(['path' => 'media/second.jpg', 'width' => 700, 'height' => 700]);

    $media = $media->fresh();

    expect(Storage::disk('public')->exists($oldCopy))->toBeFalse()
        ->and(array_keys($media->conversions))->toBe([320, 640, 700]);

    $media->delete();
    expect(Storage::disk('public')->exists($media->conversions[640]))->toBeTrue();

    $media->forceDelete();
    expect(Storage::disk('public')->exists($media->conversions[640]))->toBeFalse()
        ->and(Storage::disk('public')->exists('media/second.jpg'))->toBeTrue();
});

test('the image component serves the webp srcset with dimensions and lazy loading', function (): void {
    $media = conversionMedia('media/hero.jpg', 'image/jpeg', conversionImage(1000, 500))->fresh();

    $lazy = Blade::render('<x-mksine::image :media="$media" sizes="50vw" class="cover" />', ['media' => $media]);
    $priority = Blade::render('<x-mksine::image :media="$media" :priority="true" alt="Hero" />', ['media' => $media]);

    expect($lazy)
        ->toContain('<picture>')
        ->toContain('type="image/webp"')
        ->toContain('hero-640w.webp 640w')
        ->toContain('sizes="50vw"')
        ->toContain('width="1000" height="500"')
        ->toContain('loading="lazy"')
        ->toContain('class="cover"')
        ->toContain('/uploads/media/hero.jpg')
        ->and($priority)
        ->toContain('fetchpriority="high"')
        ->toContain('loading="eager"')
        ->toContain('alt="Hero"');
});

test('images without copies render a plain img and missing media renders nothing', function (): void {
    $media = Media::withoutEvents(fn () => conversionMediaQuietly());

    $html = Blade::render('<x-mksine::image :media="$media" />', ['media' => $media]);

    expect($html)->toContain('<img')->not->toContain('<picture>')
        ->and(trim(Blade::render('<x-mksine::image :media="null" />')))->toBe('');
});

function conversionMediaQuietly(): Media
{
    return conversionMedia('media/legacy.jpg', 'image/jpeg', conversionImage(900, 600));
}

test('the backfill command converts images uploaded before conversions existed', function (): void {
    $legacy = Media::withoutEvents(fn () => conversionMedia('media/legacy.jpg', 'image/jpeg', conversionImage(900, 600)));

    expect($legacy->fresh()->conversions)->toBeNull();

    $this->artisan('mksine:media-conversions')->assertSuccessful();

    expect(array_keys($legacy->fresh()->conversions))->toBe([320, 640, 900]);

    $this->artisan('mksine:media-conversions')->expectsOutputToContain('already')->assertSuccessful();
});
