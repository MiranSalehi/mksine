<?php

declare(strict_types=1);

use Livewire\Livewire;
use Miran\Mksine\Core\PageBuilder\Livewire\PageBuilder;

it('adds a block into columns nested inside a container column', function (): void {
    $columns = [
        'id' => 'blk_columns',
        'type' => 'columns',
        'data' => [
            'columns' => 2,
            'layout' => 'equal',
            'gap' => 'md',
            'vertical_alignment' => 'start',
            'stack_on_mobile' => 'mobile',
        ],
        'children' => [
            ['id' => 'col_a', 'items' => []],
            ['id' => 'col_b', 'items' => []],
        ],
    ];
    $container = [
        'id' => 'blk_container',
        'type' => 'container_inset',
        'data' => ['max_width' => 'full'],
        'children' => [
            ['id' => 'col_outer', 'items' => [$columns]],
        ],
    ];

    $test = Livewire::test(PageBuilder::class, ['value' => [$container]])
        ->call('addBlock', 'heading', null, 'blk_columns', 0);

    $nestedItems = $test->get('blocks')[0]['children'][0]['items'][0]['children'][0]['items'];
    expect($nestedItems)->toHaveCount(1)
        ->and($nestedItems[0]['type'])->toBe('heading');
});

it('moves a sibling block from container slot into nested columns via drag backend', function (): void {
    $heading = [
        'id' => 'blk_heading',
        'type' => 'heading',
        'data' => ['text' => 'Hi'],
        'children' => null,
    ];
    $columns = [
        'id' => 'blk_columns',
        'type' => 'columns',
        'data' => [
            'columns' => 2,
            'layout' => 'equal',
            'gap' => 'md',
            'vertical_alignment' => 'start',
            'stack_on_mobile' => 'mobile',
        ],
        'children' => [
            ['id' => 'col_a', 'items' => []],
            ['id' => 'col_b', 'items' => []],
        ],
    ];
    $container = [
        'id' => 'blk_container',
        'type' => 'container_inset',
        'data' => ['max_width' => 'full'],
        'children' => [
            ['id' => 'col_outer', 'items' => [$heading, $columns]],
        ],
    ];

    $test = Livewire::test(PageBuilder::class, ['value' => [$container]])
        ->call('moveBlockAfterDrag', 'blk_heading', 'blk_container', 0, 'blk_columns', 0, 0);

    $slotItems = $test->get('blocks')[0]['children'][0]['items'];
    expect($slotItems)->toHaveCount(1)
        ->and($slotItems[0]['id'])->toBe('blk_columns');

    $inFirstColumn = $slotItems[0]['children'][0]['items'];
    expect($inFirstColumn)->toHaveCount(1)
        ->and($inFirstColumn[0]['id'])->toBe('blk_heading');
});

it('removes a block from columns nested inside a container column', function (): void {
    $nestedHeading = [
        'id' => 'blk_heading_nested',
        'type' => 'heading',
        'data' => ['text' => 'In column'],
        'children' => null,
    ];
    $columns = [
        'id' => 'blk_columns',
        'type' => 'columns',
        'data' => [
            'columns' => 2,
            'layout' => 'equal',
            'gap' => 'md',
            'vertical_alignment' => 'start',
            'stack_on_mobile' => 'mobile',
        ],
        'children' => [
            ['id' => 'col_a', 'items' => [$nestedHeading]],
            ['id' => 'col_b', 'items' => []],
        ],
    ];
    $container = [
        'id' => 'blk_container',
        'type' => 'container_inset',
        'data' => ['max_width' => 'full'],
        'children' => [
            ['id' => 'col_outer', 'items' => [$columns]],
        ],
    ];

    Livewire::test(PageBuilder::class, ['value' => [$container]])
        ->call('removeBlock', 'blk_heading_nested', 'blk_columns', 0)
        ->tap(function ($c): void {
            $items = $c->get('blocks')[0]['children'][0]['items'][0]['children'][0]['items'];
            expect($items)->toBe([]);
        });
});

it('opens the component picker aimed at a column', function (): void {
    $columns = [
        'id' => 'blk_columns',
        'type' => 'columns',
        'data' => [
            'columns' => 2,
            'layout' => 'equal',
            'gap' => 'md',
            'vertical_alignment' => 'start',
            'stack_on_mobile' => 'mobile',
        ],
        'children' => [
            ['id' => 'col_a', 'items' => []],
            ['id' => 'col_b', 'items' => []],
        ],
    ];

    Livewire::test(PageBuilder::class, ['value' => [$columns]])
        ->call('addBlockAtPosition', 0, 'blk_columns', 0)
        ->assertSet('showComponentPanel', true)
        ->assertSet('insertAtPosition', 0)
        ->assertSet('insertInParent', 'blk_columns')
        ->assertSet('insertInColumn', '0');
});
