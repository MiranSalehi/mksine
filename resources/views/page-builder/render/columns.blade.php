{{--
    Tailwind v4 content scanner safelist — DO NOT REMOVE.
    Same reasoning as grid_layout.blade.php: responsive col-span/grid-cols classes are concatenated at
    runtime ('md:'.$colSpanClass(...) etc.), which the scanner can't detect. Listing the literals keeps
    the rules in the stylesheet across all themes that scan the page-builder views.

    sm:col-span-1 sm:col-span-2 sm:col-span-3 sm:col-span-4 sm:col-span-5 sm:col-span-6
    sm:col-span-7 sm:col-span-8 sm:col-span-9 sm:col-span-10 sm:col-span-11 sm:col-span-12
    md:col-span-1 md:col-span-2 md:col-span-3 md:col-span-4 md:col-span-5 md:col-span-6
    md:col-span-7 md:col-span-8 md:col-span-9 md:col-span-10 md:col-span-11 md:col-span-12
    lg:col-span-1 lg:col-span-2 lg:col-span-3 lg:col-span-4 lg:col-span-5 lg:col-span-6
    lg:col-span-7 lg:col-span-8 lg:col-span-9 lg:col-span-10 lg:col-span-11 lg:col-span-12
--}}
@php
    use Miran\Mksine\Core\PageBuilder\Support\TwelveColumnSpanNormalizer;

    $columns = max(2, min(12, (int) ($data['columns'] ?? 2)));
    $layout = $data['layout'] ?? 'equal';
    $gap = $data['gap'] ?? 'md';
    $verticalAlignment = $data['vertical_alignment'] ?? 'start';
    $stackOnMobile = $data['stack_on_mobile'] ?? 'mobile';

    $gapClass = match ($gap) {
        'none' => 'gap-0',
        'sm' => 'gap-2',
        'md' => 'gap-4',
        'lg' => 'gap-8',
        default => 'gap-4',
    };

    $alignmentClass = match ($verticalAlignment) {
        'center' => 'items-center',
        'end' => 'items-end',
        'stretch' => 'items-stretch',
        default => 'items-start',
    };

    $neverGrid = match ($columns) {
        2 => 'grid-cols-2',
        3 => 'grid-cols-3',
        4 => 'grid-cols-4',
        5 => 'grid-cols-5',
        6 => 'grid-cols-6',
        7 => 'grid-cols-7',
        8 => 'grid-cols-8',
        9 => 'grid-cols-9',
        10 => 'grid-cols-10',
        11 => 'grid-cols-11',
        12 => 'grid-cols-12',
        default => 'grid-cols-2',
    };

    $mobileGrid = match ($columns) {
        2 => 'grid-cols-1 sm:grid-cols-2',
        3 => 'grid-cols-1 sm:grid-cols-3',
        4 => 'grid-cols-1 sm:grid-cols-4',
        5 => 'grid-cols-1 sm:grid-cols-5',
        6 => 'grid-cols-1 sm:grid-cols-6',
        7 => 'grid-cols-1 sm:grid-cols-7',
        8 => 'grid-cols-1 sm:grid-cols-8',
        9 => 'grid-cols-1 sm:grid-cols-9',
        10 => 'grid-cols-1 sm:grid-cols-10',
        11 => 'grid-cols-1 sm:grid-cols-11',
        12 => 'grid-cols-1 sm:grid-cols-12',
        default => 'grid-cols-1 sm:grid-cols-2',
    };

    $tabletGrid = match ($columns) {
        2 => 'grid-cols-1 md:grid-cols-2',
        3 => 'grid-cols-1 md:grid-cols-3',
        4 => 'grid-cols-1 md:grid-cols-4',
        5 => 'grid-cols-1 md:grid-cols-5',
        6 => 'grid-cols-1 md:grid-cols-6',
        7 => 'grid-cols-1 md:grid-cols-7',
        8 => 'grid-cols-1 md:grid-cols-8',
        9 => 'grid-cols-1 md:grid-cols-9',
        10 => 'grid-cols-1 md:grid-cols-10',
        11 => 'grid-cols-1 md:grid-cols-11',
        12 => 'grid-cols-1 md:grid-cols-12',
        default => 'grid-cols-1 md:grid-cols-2',
    };

    $equalGridColsClass = match ($stackOnMobile) {
        'always' => 'grid-cols-1',
        'never' => $neverGrid,
        'tablet' => $tabletGrid,
        default => $mobileGrid,
    };

    $twelveGridColsClass = match ($stackOnMobile) {
        'always' => 'grid-cols-1',
        'never' => 'grid-cols-12',
        'tablet' => 'grid-cols-1 md:grid-cols-12',
        default => 'grid-cols-1 sm:grid-cols-12',
    };

    $presetSpans = TwelveColumnSpanNormalizer::layoutPresetSpans((string) $layout, $columns);
    $useTwelveColTrack = $layout !== 'equal' && $presetSpans !== [];

    if ($useTwelveColTrack) {
        $spanInts = $presetSpans;
    } else {
        $spanInts = TwelveColumnSpanNormalizer::equalSplit($columns);
    }

    /*
     * Same as grid_layout: `$columns` from block data is authoritative — do not re-split when legacy
     * payloads still carry extra child buckets.
     */
    $childBuckets = is_array($children) ? array_values($children) : [];
    $childBuckets = array_slice($childBuckets, 0, $columns);
    while (count($childBuckets) < $columns) {
        $childBuckets[] = ['items' => []];
    }

    $gridColsClass = $useTwelveColTrack ? $twelveGridColsClass : $equalGridColsClass;

    $colSpanClass = static function (int $span): string {
        return match ($span) {
            1 => 'col-span-1',
            2 => 'col-span-2',
            3 => 'col-span-3',
            4 => 'col-span-4',
            5 => 'col-span-5',
            6 => 'col-span-6',
            7 => 'col-span-7',
            8 => 'col-span-8',
            9 => 'col-span-9',
            10 => 'col-span-10',
            11 => 'col-span-11',
            12 => 'col-span-12',
            default => 'col-span-12',
        };
    };

    $cellSpanClasses = [];
    foreach ($spanInts as $i => $span) {
        $span = max(1, min(12, (int) $span));
        if ($stackOnMobile === 'always') {
            $cellSpanClasses[$i] = 'min-w-0';
        } elseif (! $useTwelveColTrack) {
            $cellSpanClasses[$i] = 'min-w-0';
        } elseif ($stackOnMobile === 'never') {
            $cellSpanClasses[$i] = $colSpanClass($span).' min-w-0';
        } elseif ($stackOnMobile === 'tablet') {
            $cellSpanClasses[$i] = 'col-span-1 md:'.$colSpanClass($span).' min-w-0';
        } else {
            $cellSpanClasses[$i] = 'col-span-1 sm:'.$colSpanClass($span).' min-w-0';
        }
    }
@endphp

<div class="grid {{ $gridColsClass }} {{ $gapClass }} {{ $alignmentClass }} mb-6">
    @foreach ($childBuckets as $colIndex => $column)
        <div class="{{ $cellSpanClasses[$colIndex] ?? 'min-w-0' }}">
            @if (! empty($column['items']))
                @foreach ($column['items'] as $item)
                    @include('mksine::page-builder.render.block', ['block' => $item])
                @endforeach
            @else
                <div class="min-h-[50px]"></div>
            @endif
        </div>
    @endforeach
</div>
