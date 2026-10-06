<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Miran\Mksine\Support\MediaStoredFile;

const MEDIA_STORED_FILE_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

function mediaStoredFilePutPng(string $path = 'media/2026/09/21/ztest.png'): string
{
    Storage::fake('public');
    Storage::disk('public')->put($path, base64_decode(MEDIA_STORED_FILE_PNG));

    return $path;
}

it('strips client-supplied file attributes', function (): void {
    $data = MediaStoredFile::forgetDerived([
        'name' => 'Hero',
        'disk' => 'public',
        'file' => 'media/2026/09/21/ztest.png',
        'file_name' => 'payload.svg',
        'mime_type' => 'image/svg+xml',
        'size' => 1,
        'width' => 9,
        'height' => 9,
        'path' => '/etc/passwd',
        'url' => 'https://evil.test/x',
    ]);

    expect($data)->toBe([
        'name' => 'Hero',
        'disk' => 'public',
        'file' => 'media/2026/09/21/ztest.png',
    ]);
});

it('overwrites a spoofed mime type from the bytes on disk', function (): void {
    $path = mediaStoredFilePutPng();

    $data = MediaStoredFile::apply(
        MediaStoredFile::forgetDerived([
            'disk' => 'public',
            'file' => $path,
            'mime_type' => 'image/svg+xml',
            'path' => 'media/stolen.svg',
            'url' => 'https://evil.test/x',
            'size' => 1,
            'width' => 99,
            'height' => 99,
            'file_name' => 'payload.svg',
        ]),
        originalFileName: 'hero.png',
    );

    expect($data['mime_type'])->toStartWith('image/png')
        ->and($data['path'])->toBe($path)
        ->and($data['url'])->not->toContain('evil.test')
        ->and($data['file_name'])->toBe('hero.png')
        ->and($data['size'])->toBeGreaterThan(1)
        ->and($data['width'])->toBe(1)
        ->and($data['height'])->toBe(1);
});

it('does not take file_name from the client when the stored path did not change', function (): void {
    $path = mediaStoredFilePutPng();

    $data = MediaStoredFile::apply(
        [
            'disk' => 'public',
            'file' => $path,
            'name' => 'Hero',
        ],
        existingPath: $path,
        originalFileName: '../../evil.svg',
    );

    expect($data)->not->toHaveKey('file_name')
        ->and($data['name'])->toBe('Hero');
});

it('rejects a relative path that escapes the media prefix', function (string $file): void {
    Storage::fake('public');
    Storage::disk('public')->put('secrets.txt', 'token');

    expect(fn () => MediaStoredFile::apply([
        'disk' => 'public',
        'file' => $file,
    ]))->toThrow(ValidationException::class);
})->with([
    'parent directory' => ['media/../secrets.txt'],
    'nested climb' => ['media/2026/../../secrets.txt'],
    'dot segment' => ['media/./ztest.png'],
]);

it('accepts only paths under the media prefix', function (string $relative, bool $safe): void {
    expect(MediaStoredFile::isSafeRelativePath($relative))->toBe($safe);
})->with([
    'dated png' => ['media/2026/09/21/ztest.png', true],
    'bare filename' => ['ztest.png', false],
    'absolute' => ['/etc/passwd', false],
    'scheme' => ['file://media/ztest.png', false],
    'windows drive' => ['C:media/ztest.png', false],
    'null byte' => ["media/ztest.png\0.svg", false],
]);
