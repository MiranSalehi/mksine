<?php

declare(strict_types=1);

use Miran\Mksine\Core\PageBuilder\Components\ColumnsComponent;

test('columns component allows up to twelve child column slots', function (): void {
    expect(ColumnsComponent::getMaxChildren())->toBe(12);
});

test('columns render emits twelve-column grid class when stack never', function (): void {
    $children = array_fill(0, 12, ['id' => 'col', 'items' => []]);

    $html = view('mksine::page-builder.render.columns', [
        'data' => [
            'columns' => 12,
            'layout' => 'equal',
            'gap' => 'md',
            'vertical_alignment' => 'start',
            'stack_on_mobile' => 'never',
        ],
        'children' => $children,
    ])->render();

    expect($html)->toContain('grid-cols-12');
});

test('columns render emits md twelve-column grid class for tablet stack mode', function (): void {
    $children = array_fill(0, 12, ['id' => 'col', 'items' => []]);

    $html = view('mksine::page-builder.render.columns', [
        'data' => [
            'columns' => 12,
            'layout' => 'equal',
            'gap' => 'md',
            'vertical_alignment' => 'start',
            'stack_on_mobile' => 'tablet',
        ],
        'children' => $children,
    ])->render();

    expect($html)->toContain('md:grid-cols-12');
});
