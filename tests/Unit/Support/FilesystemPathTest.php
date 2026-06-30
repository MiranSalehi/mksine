<?php

declare(strict_types=1);

use Miran\Mksine\Support\FilesystemPath;

test('relative path handles mixed windows and unix separators', function (): void {
    $base = 'D:\\Programing\\www\\modireshop\\vendor\\miran\\mksine\\resources\\lang';
    $path = 'D:/Programing/www/modireshop/vendor/miran/mksine/resources/lang/en/mksine.php';

    expect(FilesystemPath::relativeTo($base, $path))->toBe('en/mksine.php');
});

test('relative path handles consistent unix separators', function (): void {
    $base = '/var/www/vendor/miran/mksine/resources/lang';
    $path = '/var/www/vendor/miran/mksine/resources/lang/fa/dashboard.php';

    expect(FilesystemPath::relativeTo($base, $path))->toBe('fa/dashboard.php');
});

test('relative path handles json files at lang root', function (): void {
    $base = 'C:\\app\\vendor\\miran\\mksine\\resources\\lang';
    $path = 'C:/app/vendor/miran/mksine/resources/lang/en.json';

    expect(FilesystemPath::relativeTo($base, $path))->toBe('en.json');
});

test('relative path uses real package lang directory on disk', function (): void {
    $base = realpath(__DIR__.'/../../resources/lang');

    expect($base)->not->toBeFalse();

    $files = glob($base.'/*/*.php') ?: [];
    expect($files)->not->toBeEmpty();

    $sample = $files[0];
    $mixed = str_replace('/', '\\', dirname($sample)).'/'.basename($sample);

    expect(FilesystemPath::relativeTo($base, $mixed))
        ->toBe(FilesystemPath::relativeTo($base, $sample));
});
