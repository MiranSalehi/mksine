<?php

declare(strict_types=1);

use Miran\Mksine\Core\PageBuilder\Support\TwelveColumnSpanNormalizer;

test('normalizeRepeaterSpans preserves widths that total twelve', function (): void {
    $out = TwelveColumnSpanNormalizer::normalizeRepeaterSpans([
        ['span' => 2],
        ['span' => 10],
    ], 2);

    expect($out)->toBe([['span' => 2], ['span' => 10]]);
});

test('normalizeRepeaterSpans maps arbitrary weights onto twelve columns', function (): void {
    $out = TwelveColumnSpanNormalizer::normalizeRepeaterSpans([
        ['span' => 5],
        ['span' => 5],
    ], 2);

    expect(array_sum(array_column($out, 'span')))->toBe(12);
});

test('layout preset spans encode classic two and three column ratios', function (): void {
    expect(TwelveColumnSpanNormalizer::layoutPresetSpans('1-2', 2))->toBe([4, 8])
        ->and(TwelveColumnSpanNormalizer::layoutPresetSpans('equal', 2))->toBe([])
        ->and(TwelveColumnSpanNormalizer::layoutPresetSpans('1-2-1', 3))->toBe([3, 6, 3]);
});

test('equalSplit partitions twelve units across column count', function (): void {
    expect(TwelveColumnSpanNormalizer::equalSplit(3))->toBe([4, 4, 4])
        ->and(array_sum(TwelveColumnSpanNormalizer::equalSplit(5)))->toBe(12);
});
