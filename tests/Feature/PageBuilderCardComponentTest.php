<?php

declare(strict_types=1);

use Miran\Mksine\Core\PageBuilder\ComponentRegistry;
use Miran\Mksine\Core\PageBuilder\Components\CardComponent;

test('card keeps an allowlisted style and three content bands', function (): void {
    $data = CardComponent::validate([
        'show_header' => 'yes',
        'show_footer' => 0,
        'background_color' => 'red',
        'radius' => 'huge',
        'header_color' => '#AABBCC',
    ]);

    expect($data['show_header'])->toBeTrue()
        ->and($data['show_footer'])->toBeFalse()
        ->and($data['background_color'])->toBe('')
        ->and($data['radius'])->toBe('md')
        ->and($data['header_color'])->toBe('#aabbcc')
        ->and(CardComponent::createInstance()['children'])->toHaveCount(3);

    $html = view('mksine::page-builder.render.card', [
        'data' => $data,
        'blockId' => 'card_1',
        'preview' => true,
        'canvas' => true,
        'children' => [
            ['id' => 'h', 'items' => []],
            ['id' => 'b', 'items' => [
                ['id' => 'text_1', 'type' => 'heading', 'data' => ['text' => 'Inside', 'level' => 'h3']],
            ]],
            ['id' => 'f', 'items' => [
                ['id' => 'button_1', 'type' => 'button', 'data' => ['text' => 'Hidden', 'url' => '/go']],
            ]],
        ],
    ])->render();

    expect($html)->toContain('<header')
        ->and($html)->toContain('Inside')
        ->and($html)->toContain('data-mksine-column="1"')
        ->and($html)->toContain('background-color: #ffffff')
        ->and($html)->toContain('color: #aabbcc')
        ->and($html)->not->toContain('<footer')
        ->and($html)->not->toContain('Hidden');
});

test('card footer renders when it is enabled', function (): void {
    $html = view('mksine::page-builder.render.card', [
        'data' => ['show_header' => false, 'show_footer' => true],
        'blockId' => 'card_2',
        'children' => [
            ['items' => []],
            ['items' => []],
            ['items' => []],
        ],
    ])->render();

    expect($html)->not->toContain('<header')
        ->and($html)->toContain('<footer')
        ->and(app(ComponentRegistry::class)->has('card'))->toBeTrue();
});
