<?php

declare(strict_types=1);

use Miran\Mksine\Models\Media;

describe('Media Model', function () {
    it('has correct fillable attributes', function () {
        $media = new Media;
        $fillable = $media->getFillable();

        expect($fillable)->toContain('name');
        expect($fillable)->toContain('file_name');
        expect($fillable)->toContain('mime_type');
        expect($fillable)->toContain('size');
        expect($fillable)->toContain('width');
        expect($fillable)->toContain('height');
        expect($fillable)->toContain('disk');
        expect($fillable)->toContain('path');
        expect($fillable)->toContain('url');
    });

    it('casts size to integer', function () {
        $media = new Media;
        $casts = $media->getCasts();

        expect($casts['size'])->toBe('integer');
    });

    it('casts width and height to integer', function () {
        $media = new Media;
        $casts = $media->getCasts();

        expect($casts['width'])->toBe('integer');
        expect($casts['height'])->toBe('integer');
    });

    it('can check if media is image', function () {
        $imageMedia = new Media(['mime_type' => 'image/jpeg']);
        $videoMedia = new Media(['mime_type' => 'video/mp4']);
        $docMedia = new Media(['mime_type' => 'application/pdf']);

        expect($imageMedia->isImage())->toBeTrue();
        expect($videoMedia->isImage())->toBeFalse();
        expect($docMedia->isImage())->toBeFalse();
    });

    it('can check if media is video', function () {
        $imageMedia = new Media(['mime_type' => 'image/jpeg']);
        $videoMedia = new Media(['mime_type' => 'video/mp4']);
        $docMedia = new Media(['mime_type' => 'application/pdf']);

        expect($imageMedia->isVideo())->toBeFalse();
        expect($videoMedia->isVideo())->toBeTrue();
        expect($docMedia->isVideo())->toBeFalse();
    });

    it('can check if media is document', function () {
        $pdfMedia = new Media(['mime_type' => 'application/pdf']);
        $wordMedia = new Media(['mime_type' => 'application/msword']);
        $imageMedia = new Media(['mime_type' => 'image/jpeg']);

        expect($pdfMedia->isDocument())->toBeTrue();
        expect($wordMedia->isDocument())->toBeTrue();
        expect($imageMedia->isDocument())->toBeFalse();
    });

    it('provides human readable file size', function () {
        $smallFile = new Media(['size' => 500]);
        $kilobyteFile = new Media(['size' => 2048]);
        $megabyteFile = new Media(['size' => 2 * 1024 * 1024]);

        expect($smallFile->human_size)->toBe('500 B');
        expect($kilobyteFile->human_size)->toBe('2 KB');
        expect($megabyteFile->human_size)->toBe('2 MB');
    });

    it('uses soft deletes', function () {
        $media = new Media;

        expect(in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive($media)))->toBeTrue();
    });
});
