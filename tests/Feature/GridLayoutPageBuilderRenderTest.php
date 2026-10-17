<?php

declare(strict_types=1);

test('grid layout render keeps ten and two col-spans when twelve legacy child buckets remain', function (): void {
    $children = array_fill(0, 12, ['id' => 'bucket', 'items' => []]);

    $html = view('mksine::page-builder.render.grid_layout', [
        'data' => [
            'columns' => 2,
            'column_spans' => [
                ['span' => 10],
                ['span' => 2],
            ],
            'gap' => 'md',
            'vertical_alignment' => 'start',
            'stack_on_mobile' => 'mobile',
        ],
        'children' => $children,
    ])->render();

    expect($html)
        ->toContain('grid-cols-12')
        ->toContain('col-span-10')
        ->toContain('col-span-2')
        ->toContain('max-sm:col-span-12')
        ->and(substr_count($html, 'col-span-10'))->toBe(1)
        ->and(substr_count($html, 'col-span-2'))->toBe(1);
});

test('grid layout render emits md breakpoint col-span classes when span_md set', function (): void {
    $children = array_fill(0, 2, ['id' => 'bucket', 'items' => []]);

    $html = view('mksine::page-builder.render.grid_layout', [
        'data' => [
            'columns' => 2,
            'column_spans' => [
                ['span' => 6, 'span_md' => 8],
                ['span' => 6, 'span_md' => 4],
            ],
            'gap' => 'md',
            'vertical_alignment' => 'start',
            'stack_on_mobile' => 'mobile',
        ],
        'children' => $children,
    ])->render();

    expect($html)
        ->toContain('md:col-span-8')
        ->toContain('md:col-span-4')
        ->toContain('col-span-6')
        ->toContain('max-sm:col-span-12');
});

test('grid layout render emits sm and md full-width then lg split when all regions use 12 at sm md', function (): void {
    $children = array_fill(0, 2, ['id' => 'bucket', 'items' => []]);

    $html = view('mksine::page-builder.render.grid_layout', [
        'data' => [
            'columns' => 2,
            'column_spans' => [
                ['span' => 10, 'span_sm' => 12, 'span_md' => 12, 'span_lg' => 10],
                ['span' => 2, 'span_sm' => 12, 'span_md' => 12, 'span_lg' => 2],
            ],
            'gap' => 'md',
            'vertical_alignment' => 'start',
            'stack_on_mobile' => 'mobile',
        ],
        'children' => $children,
    ])->render();

    expect($html)
        ->toContain('grid-cols-12')
        ->toContain('sm:col-span-12')
        ->toContain('md:col-span-12')
        ->toContain('lg:col-span-10')
        ->toContain('lg:col-span-2')
        ->toContain('col-span-10')
        ->toContain('col-span-2')
        ->toContain('max-sm:col-span-12')
        ->not->toContain('sm:col-span-10')
        ->not->toContain('sm:col-span-2');
});

test('columns render keeps three equal tracks when stale extra child buckets remain', function (): void {
    $children = array_fill(0, 8, ['id' => 'bucket', 'items' => []]);

    $html = view('mksine::page-builder.render.columns', [
        'data' => [
            'columns' => 3,
            'layout' => 'equal',
            'gap' => 'md',
            'vertical_alignment' => 'start',
            'stack_on_mobile' => 'mobile',
        ],
        'children' => $children,
    ])->render();

    expect(substr_count($html, 'grid-cols-1 sm:grid-cols-3'))->toBe(1)
        ->and(substr_count($html, 'mksine-region-cell'))->toBe(3)
        ->and($html)->not->toContain('min-h-[50px]')
        ->and($html)->not->toContain('data-mksine-insert');
});

test('canvas columns expose insert targets inside each region', function (): void {
    $html = view('mksine::page-builder.render.block', [
        'block' => [
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
                [
                    'id' => 'col_a',
                    'items' => [[
                        'id' => 'blk_heading',
                        'type' => 'heading',
                        'data' => ['text' => 'Inside', 'level' => 'h2', 'alignment' => 'left'],
                        'children' => null,
                    ]],
                ],
                ['id' => 'col_b', 'items' => []],
            ],
        ],
        'preview' => true,
        'canvas' => true,
    ])->render();

    expect($html)
        ->toContain('data-mksine-parent="blk_columns"')
        ->toContain('data-mksine-column="0"')
        ->toContain('data-mksine-column="1"')
        ->toContain('data-mksine-parent-id="blk_columns"')
        ->toContain('data-mksine-column-index="0"')
        ->toContain('data-mksine-action="duplicate"')
        ->not->toContain('min-h-[50px]');
});
