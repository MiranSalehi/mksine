<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Miran\Mksine\Core\PageBuilder\ComponentRegistry;
use Miran\Mksine\Core\PageBuilder\Components\AudioComponent;
use Miran\Mksine\Core\PageBuilder\Components\VideoComponent;
use Miran\Mksine\Models\Media;

uses(RefreshDatabase::class);

it('registers video and audio page builder components', function (): void {
    $registry = app(ComponentRegistry::class);

    expect($registry->has('video'))->toBeTrue()
        ->and($registry->has('audio'))->toBeTrue();

    expect(VideoComponent::getDefaultData()['video'])->toBeNull()
        ->and(AudioComponent::getDefaultData()['audio'])->toBeNull();
});

it('renders a video block from a media id', function (): void {
    $media = Media::query()->create([
        'name' => 'clip',
        'file_name' => 'clip.mp4',
        'mime_type' => 'video/mp4',
        'path' => 'media/clip.mp4',
        'disk' => 'public',
        'size' => 4096,
        'url' => '/storage/media/clip.mp4',
    ]);

    $html = view('mksine::page-builder.render.video', [
        'data' => [
            'video' => $media->id,
            'caption' => 'Demo',
            'controls' => true,
            'autoplay' => false,
            'loop' => false,
        ],
    ])->render();

    expect($html)->toContain('<video')
        ->and($html)->toContain('Demo')
        ->and($html)->not->toContain(__('mksine::page_builder.no_video_selected'));
});

it('renders an audio block from a media id', function (): void {
    $media = Media::query()->create([
        'name' => 'track',
        'file_name' => 'track.mp3',
        'mime_type' => 'audio/mpeg',
        'path' => 'media/track.mp3',
        'disk' => 'public',
        'size' => 2048,
        'url' => '/storage/media/track.mp3',
    ]);

    $html = view('mksine::page-builder.render.audio', [
        'data' => [
            'audio' => $media->id,
            'caption' => 'Theme',
            'controls' => true,
            'autoplay' => false,
            'loop' => false,
        ],
    ])->render();

    expect($html)->toContain('<audio')
        ->and($html)->toContain('Theme')
        ->and($html)->not->toContain(__('mksine::page_builder.no_audio_selected'));
});
