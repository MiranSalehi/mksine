<?php

declare(strict_types=1);

use Livewire\Livewire;
use Miran\Mksine\Core\PageBuilder\Components\GridLayoutComponent;
use Miran\Mksine\Core\PageBuilder\Livewire\ComponentEditor;

test('block appearance sits on its own tab and still saves', function (): void {
    $editor = Livewire::test(ComponentEditor::class, [
        'blockId' => 'blk_heading',
        'blockType' => 'heading',
        'blockData' => ['text' => 'Hello', 'level' => 'h2', 'alignment' => 'left'],
        'presentation' => ['spacing' => 'md', 'visibility' => 'hide_mobile'],
    ]);

    $editor->assertSee(__('mksine::page_builder.component_labels.content'))
        ->assertSee(__('mksine::page_builder.presentation_section'))
        ->call('save')
        ->assertDispatched('saveBlockData', function (string $_name, array $params): bool {
            return ($params['blockId'] ?? null) === 'blk_heading'
                && ($params['data']['text'] ?? null) === 'Hello'
                && ($params['presentation']['spacing'] ?? null) === 'md'
                && ($params['presentation']['visibility'] ?? null) === 'hide_mobile';
        });

    Livewire::test(ComponentEditor::class, [
        'blockId' => 'fin',
        'blockType' => 'mksine_finance_showcase',
        'blockData' => ['heading' => 'Only this'],
        'region' => 'heading',
    ])->assertDontSee(__('mksine::page_builder.presentation_section'));
});

test('grid layout editor save dispatches full repeater row data including span_lg', function (): void {
    $blockData = GridLayoutComponent::validate([
        'column_spans' => [
            ['span' => 10, 'span_lg' => 10],
            ['span' => 2, 'span_lg' => 2],
        ],
        'gap' => 'md',
        'vertical_alignment' => 'start',
        'stack_on_mobile' => 'mobile',
    ]);

    Livewire::test(ComponentEditor::class, [
        'blockId' => 'blk_grid',
        'blockType' => 'grid_layout',
        'blockData' => $blockData,
        'parentId' => null,
        'columnIndex' => null,
    ])
        ->call('save')
        ->assertDispatched('saveBlockData', function (string $_name, array $params): bool {
            if (($params['blockId'] ?? null) !== 'blk_grid') {
                return false;
            }

            $rows = array_values((array) (($params['data']['column_spans'] ?? [])));

            if (count($rows) < 2) {
                return false;
            }

            return (int) ($rows[0]['span'] ?? 0) === 10
                && (int) ($rows[0]['span_lg'] ?? 0) === 10
                && (int) ($rows[1]['span'] ?? 0) === 2
                && (int) ($rows[1]['span_lg'] ?? 0) === 2;
        });
});
