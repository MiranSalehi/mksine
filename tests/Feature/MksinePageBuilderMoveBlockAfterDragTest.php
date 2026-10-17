<?php

declare(strict_types=1);

use Livewire\Livewire;
use Miran\Mksine\Core\PageBuilder\Livewire\PageBuilder;

it('reorders blocks dropped in the same list', function (): void {
    $first = [
        'id' => 'blk_a',
        'type' => 'heading',
        'data' => ['text' => 'A'],
        'children' => null,
    ];
    $second = [
        'id' => 'blk_b',
        'type' => 'heading',
        'data' => ['text' => 'B'],
        'children' => null,
    ];

    $reordered = Livewire::test(PageBuilder::class, ['value' => [$first, $second]])
        ->call('moveBlockAfterDrag', 'blk_a', null, null, null, null, 1);

    expect(array_column($reordered->get('blocks'), 'id'))->toBe(['blk_b', 'blk_a']);

    $unchanged = Livewire::test(PageBuilder::class, ['value' => [$first, $second]])
        ->call('moveBlockAfterDrag', 'blk_a', null, null, null, null, 0);

    expect(array_column($unchanged->get('blocks'), 'id'))->toBe(['blk_a', 'blk_b']);
});

it('moves a root block into an empty parent column', function (): void {
    $heading = [
        'id' => 'blk_heading',
        'type' => 'heading',
        'data' => ['text' => 'Hello'],
        'children' => null,
    ];
    $container = [
        'id' => 'blk_container',
        'type' => 'container_inset',
        'data' => ['max_width' => 'full'],
        'children' => [
            ['id' => 'col_1', 'items' => []],
        ],
    ];

    $test = Livewire::test(PageBuilder::class, ['value' => [$heading, $container]])
        ->call('moveBlockAfterDrag', 'blk_heading', null, null, 'blk_container', 0, 0);

    $blocks = $test->get('blocks');
    expect($blocks)->toHaveCount(1)
        ->and($blocks[0]['id'])->toBe('blk_container')
        ->and($blocks[0]['children'][0]['items'])->toHaveCount(1)
        ->and($blocks[0]['children'][0]['items'][0]['id'])->toBe('blk_heading');
});

it('moves a block from a column to the root list', function (): void {
    $heading = [
        'id' => 'blk_heading',
        'type' => 'heading',
        'data' => ['text' => 'Hello'],
        'children' => null,
    ];
    $container = [
        'id' => 'blk_container',
        'type' => 'container_inset',
        'data' => ['max_width' => 'full'],
        'children' => [
            ['id' => 'col_1', 'items' => [$heading]],
        ],
    ];

    $test = Livewire::test(PageBuilder::class, ['value' => [$container]])
        ->call('moveBlockAfterDrag', 'blk_heading', 'blk_container', 0, null, null, 0);

    $blocks = $test->get('blocks');
    expect($blocks)->toHaveCount(2)
        ->and($blocks[0]['id'])->toBe('blk_heading')
        ->and($blocks[1]['id'])->toBe('blk_container')
        ->and($blocks[1]['children'][0]['items'])->toBe([]);
});

it('does not move a parent block into its own descendant', function (): void {
    $inner = [
        'id' => 'blk_inner',
        'type' => 'container_inset',
        'data' => ['max_width' => 'full'],
        'children' => [
            ['id' => 'col_inner', 'items' => []],
        ],
    ];
    $outer = [
        'id' => 'blk_outer',
        'type' => 'container_inset',
        'data' => ['max_width' => 'full'],
        'children' => [
            ['id' => 'col_outer', 'items' => [$inner]],
        ],
    ];
    $initial = [$outer];

    $test = Livewire::test(PageBuilder::class, ['value' => $initial])
        ->call('moveBlockAfterDrag', 'blk_outer', null, null, 'blk_inner', 0, 0);

    expect($test->get('blocks'))->toEqual($initial);
});

it('reorders nested column items when parent is not at root only', function (): void {
    $leafB = ['id' => 'blk_b', 'type' => 'heading', 'data' => ['text' => 'B'], 'children' => null];
    $leafA = ['id' => 'blk_a', 'type' => 'heading', 'data' => ['text' => 'A'], 'children' => null];
    $inner = [
        'id' => 'blk_inner',
        'type' => 'container_inset',
        'data' => ['max_width' => 'full'],
        'children' => [
            ['id' => 'col_inner', 'items' => [$leafA, $leafB]],
        ],
    ];
    $outer = [
        'id' => 'blk_outer',
        'type' => 'container_inset',
        'data' => ['max_width' => 'full'],
        'children' => [
            ['id' => 'col_outer', 'items' => [$inner]],
        ],
    ];

    $test = Livewire::test(PageBuilder::class, ['value' => [$outer]])
        ->call('reorderColumnBlocks', 'blk_inner', 0, ['blk_b', 'blk_a']);

    $blocks = $test->get('blocks');
    $items = $blocks[0]['children'][0]['items'][0]['children'][0]['items'];
    expect($items[0]['id'])->toBe('blk_b')
        ->and($items[1]['id'])->toBe('blk_a');
});

it('opens the editor for a block nested in a container', function (): void {
    $heading = [
        'id' => 'blk_heading',
        'type' => 'heading',
        'data' => ['text' => 'Inside', 'level' => 'h2', 'alignment' => 'left'],
        'children' => null,
    ];
    $container = [
        'id' => 'blk_container',
        'type' => 'container_inset',
        'data' => ['max_width' => 'full'],
        'children' => [
            ['id' => 'col_1', 'items' => [$heading]],
        ],
    ];

    Livewire::test(PageBuilder::class, ['value' => [$container]])
        ->call('editBlock', 'blk_heading', 'blk_container', 0)
        ->assertSet('editingBlockId', 'blk_heading')
        ->assertSet('editingBlockData.parentId', 'blk_container')
        ->assertSet('editingBlockData.columnIndex', 0);
});
