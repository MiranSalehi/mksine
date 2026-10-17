<?php

declare(strict_types=1);

use Miran\Mksine\Core\PageBuilder\Components\GridLayoutComponent;

test('grid layout validate coerces zones count from repeater when fewer spans than declared', function (): void {
    $out = GridLayoutComponent::validate([
        'columns' => 12,
        'column_spans' => [
            ['span' => 2],
            ['span' => 10],
        ],
        'gap' => 'md',
        'vertical_alignment' => 'start',
        'stack_on_mobile' => 'mobile',
    ]);

    expect($out['columns'])->toBe(2)
        ->and($out['column_spans'])->toBe([['span' => 2], ['span' => 10]]);
});

test('grid layout validate clamps zone count when more than twelve span rows arrive', function (): void {
    $raw = [];
    for ($i = 0; $i < 14; $i++) {
        $raw[] = ['span' => 1];
    }

    $out = GridLayoutComponent::validate([
        'columns' => 12,
        'column_spans' => $raw,
        'gap' => 'md',
        'vertical_alignment' => 'start',
        'stack_on_mobile' => 'mobile',
    ]);

    expect($out['columns'])->toBe(12)
        ->and($out['column_spans'])->toHaveCount(12);
});

test('grid layout validate keeps optional breakpoint spans in range', function (): void {
    $out = GridLayoutComponent::validate([
        'column_spans' => [
            ['span' => 6, 'span_md' => 10, 'span_lg' => 12],
            ['span' => 6],
        ],
        'gap' => 'md',
        'vertical_alignment' => 'start',
        'stack_on_mobile' => 'mobile',
    ]);

    expect($out['column_spans'][0])->toMatchArray([
        'span' => 6,
        'span_md' => 10,
        'span_lg' => 12,
    ]);
});

test('grid layout validate strips breakpoint spans outside 1–12', function (): void {
    $out = GridLayoutComponent::validate([
        'column_spans' => [
            ['span' => 6, 'span_md' => 0],
            ['span' => 6],
        ],
        'gap' => 'md',
        'vertical_alignment' => 'start',
        'stack_on_mobile' => 'mobile',
    ]);

    expect($out['column_spans'][0])->not->toHaveKey('span_md');

    $outWide = GridLayoutComponent::validate([
        'column_spans' => [
            ['span' => 6, 'span_md' => 99],
            ['span' => 6],
        ],
        'gap' => 'md',
        'vertical_alignment' => 'start',
        'stack_on_mobile' => 'mobile',
    ]);

    expect($outWide['column_spans'][0])->not->toHaveKey('span_md');
});

test('grid layout validate keeps explicit uniform span_sm and span_md of 12 on every region', function (): void {
    $out = GridLayoutComponent::validate([
        'column_spans' => [
            ['span' => 10, 'span_sm' => 12, 'span_md' => 12, 'span_lg' => 10],
            ['span' => 2, 'span_sm' => 12, 'span_md' => 12, 'span_lg' => 2],
        ],
        'gap' => 'md',
        'vertical_alignment' => 'start',
        'stack_on_mobile' => 'mobile',
    ]);

    expect($out['column_spans'][0])->toMatchArray([
        'span' => 10,
        'span_sm' => 12,
        'span_md' => 12,
        'span_lg' => 10,
    ])->and($out['column_spans'][1])->toMatchArray([
        'span' => 2,
        'span_sm' => 12,
        'span_md' => 12,
        'span_lg' => 2,
    ]);
});

test('grid layout validate keeps span_sm when regions disagree at sm', function (): void {
    $out = GridLayoutComponent::validate([
        'column_spans' => [
            ['span' => 10, 'span_sm' => 12],
            ['span' => 2, 'span_sm' => 6],
        ],
        'gap' => 'md',
        'vertical_alignment' => 'start',
        'stack_on_mobile' => 'mobile',
    ]);

    expect($out['column_spans'][0]['span_sm'])->toBe(12)
        ->and($out['column_spans'][1]['span_sm'])->toBe(6);
});
