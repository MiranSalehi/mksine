<?php

declare(strict_types=1);

use Carbon\Carbon;
use Miran\Mksine\Support\MediaStoragePath;

test('dated directory uses media prefix and year month day segments', function (): void {
    $at = Carbon::parse('2026-07-05 14:30:00');

    expect(MediaStoragePath::datedDirectory($at))->toBe('media/2026/07/05');
});

test('relative path joins dated directory and file name', function (): void {
    $at = Carbon::parse('2026-07-05 14:30:00');

    expect(MediaStoragePath::relativePath('photo.jpg', $at))
        ->toBe('media/2026/07/05/photo.jpg');
});
