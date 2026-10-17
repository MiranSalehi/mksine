<?php

declare(strict_types=1);

use Livewire\Livewire;
use Miran\Mksine\Core\PageBuilder\Livewire\PageBuilder;

it('drops an unknown pasted block and does not record history', function (): void {
    $test = Livewire::test(PageBuilder::class, ['value' => []])
        ->call('pasteBlock', json_encode([
            'type' => 'not_a_real_block',
            'data' => ['text' => 'nope'],
        ]));

    expect($test->get('blocks'))->toBe([])
        ->and($test->instance()->canUndo())->toBeFalse();
});

it('normalizes a pasted button url and records history only after success', function (): void {
    $test = Livewire::test(PageBuilder::class, ['value' => []])
        ->call('pasteBlock', json_encode([
            'id' => 'kept',
            'type' => 'button',
            'data' => [
                'text' => 'Go',
                'url' => 'javascript:alert(1)',
            ],
        ]));

    $blocks = $test->get('blocks');

    expect($blocks)->toHaveCount(1)
        ->and($blocks[0]['type'])->toBe('button')
        ->and($blocks[0]['data']['url'])->toBe('#')
        ->and($blocks[0]['id'])->not->toBe('kept')
        ->and($test->instance()->canUndo())->toBeTrue();
});

it('keeps a block the reorder payload omitted', function (): void {
    $first = [
        'id' => 'block_a',
        'type' => 'heading',
        'data' => ['text' => 'A', 'level' => 'h2', 'alignment' => 'left'],
        'children' => null,
    ];
    $second = [
        'id' => 'block_b',
        'type' => 'heading',
        'data' => ['text' => 'B', 'level' => 'h2', 'alignment' => 'left'],
        'children' => null,
    ];

    $blocks = Livewire::test(PageBuilder::class, ['value' => [$first, $second]])
        ->call('reorderBlocks', ['block_b'])
        ->get('blocks');

    expect($blocks)->toHaveCount(2)
        ->and($blocks[0]['id'])->toBe('block_b')
        ->and($blocks[1]['id'])->toBe('block_a');
});

it('renders a forged heading level as h2', function (): void {
    $html = view('mksine::page-builder.render.heading', [
        'data' => [
            'text' => 'Title',
            'level' => 'h2 onclick=alert(1)',
            'alignment' => 'left',
        ],
    ])->render();

    expect($html)->toContain('<h2')
        ->and($html)->not->toContain('onclick');
});

it('stores presentation beside block data and ignores a forged anchor', function (): void {
    $block = [
        'id' => 'block_a',
        'type' => 'heading',
        'data' => ['text' => 'A', 'level' => 'h2', 'alignment' => 'left'],
        'children' => null,
    ];

    $saved = Livewire::test(PageBuilder::class, ['value' => [$block]])
        ->call('editBlock', 'block_a')
        ->call('saveBlock', [
            'text' => 'A',
            'level' => 'h2',
            'alignment' => 'left',
            'presentation' => [
                'anchor' => 'Intro Section',
                'spacing' => 'sm',
                'visibility' => 'hide_mobile',
            ],
        ])
        ->get('blocks');

    expect($saved[0]['data'])->not->toHaveKey('presentation')
        ->and($saved[0]['presentation']['anchor'])->toBe('')
        ->and($saved[0]['presentation']['spacing'])->toBe('sm')
        ->and($saved[0]['presentation']['visibility'])->toBe('hide_mobile');
});

it('wraps a rendered block with the shared anchor and visibility classes', function (): void {
    $html = view('mksine::page-builder.render.block', [
        'block' => [
            'id' => 'block_a',
            'type' => 'heading',
            'data' => ['text' => 'Title', 'level' => 'h2', 'alignment' => 'left'],
            'children' => null,
            'presentation' => [
                'anchor' => 'Hero',
                'spacing' => 'lg',
                'visibility' => 'hide_desktop',
            ],
        ],
    ])->render();

    expect($html)->toContain('id="hero"')
        ->toContain('margin-bottom: 2rem')
        ->toContain('md:hidden')
        ->toContain('Title');
});
