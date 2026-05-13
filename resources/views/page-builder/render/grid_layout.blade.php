{{--
    Tailwind v4 content scanner safelist — DO NOT REMOVE.
    Responsive col-span classes below are produced via runtime string concat (e.g. 'sm:'.$colSpanClass(...)),
    which Tailwind cannot detect as candidates. Keeping each variant as a literal token in this blade source
    forces them into the generated stylesheet. When adding new breakpoints update this list too.

    sm:col-span-1 sm:col-span-2 sm:col-span-3 sm:col-span-4 sm:col-span-5 sm:col-span-6
    sm:col-span-7 sm:col-span-8 sm:col-span-9 sm:col-span-10 sm:col-span-11 sm:col-span-12
    md:col-span-1 md:col-span-2 md:col-span-3 md:col-span-4 md:col-span-5 md:col-span-6
    md:col-span-7 md:col-span-8 md:col-span-9 md:col-span-10 md:col-span-11 md:col-span-12
    lg:col-span-1 lg:col-span-2 lg:col-span-3 lg:col-span-4 lg:col-span-5 lg:col-span-6
    lg:col-span-7 lg:col-span-8 lg:col-span-9 lg:col-span-10 lg:col-span-11 lg:col-span-12
    max-sm:col-span-12 max-md:col-span-12
--}}
@php
    use Miran\Mksine\Core\PageBuilder\Components\GridLayoutComponent;

    $data = GridLayoutComponent::validate(is_array($data ?? null) ? $data : []);

    $columns = max(2, min(12, (int) ($data['columns'] ?? 2)));
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

    $twelveGridColsClass = match ($stackOnMobile) {
        'always' => 'grid-cols-1',
        'never' => 'grid-cols-12',
        /* 12 tracks at all widths; cells use max-sm / max-md full-width to stack when “stack on mobile/tablet”. */
        'tablet' => 'grid-cols-12',
        default => 'grid-cols-12',
    };

    /*
     * Authoritative track count is `$data['columns']` (and matching `column_spans`), not `count($children)`.
     * Legacy payloads can still list extra column buckets after shrinking zones in the editor — if we
     * re-split spans to match that stale count, every cell becomes col-span-1 and content clumps on one side.
     */
    $spanRows = array_values((array) ($data['column_spans'] ?? []));
    $spanRows = array_slice($spanRows, 0, $columns);
    while (count($spanRows) < $columns) {
        $spanRows[] = ['span' => 6];
    }

    $childBuckets = is_array($children) ? array_values($children) : [];
    $childBuckets = array_slice($childBuckets, 0, $columns);
    while (count($childBuckets) < $columns) {
        $childBuckets[] = ['items' => []];
    }

    $colSpanClass = static function (int $span): string {
        return match (max(1, min(12, $span))) {
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

    /** @var array<int, mixed> */
    $optInt = static function (?array $row, string $key): ?int {
        if ($row === null || !isset($row[$key])) {
            return null;
        }

        return max(1, min(12, (int) $row[$key]));
    };

    $cellSpanClasses = [];
    foreach ($spanRows as $i => $row) {
        $row = is_array($row) ? $row : [];
        $base = max(1, min(12, (int) ($row['span'] ?? 1)));
        $sm = $optInt($row, 'span_sm');
        $md = $optInt($row, 'span_md');
        $lg = $optInt($row, 'span_lg');

        if ($stackOnMobile === 'always') {
            $cellSpanClasses[$i] = 'min-w-0';

            continue;
        }

        if ($stackOnMobile === 'never') {
            $parts = [$colSpanClass($base)];
            if ($sm !== null) {
                $parts[] = 'sm:' . $colSpanClass($sm);
            }
            if ($md !== null) {
                $parts[] = 'md:' . $colSpanClass($md);
            }
            if ($lg !== null) {
                $parts[] = 'lg:' . $colSpanClass($lg);
            }
            $cellSpanClasses[$i] = implode(' ', $parts) . ' min-w-0';

            continue;
        }

        if ($stackOnMobile === 'tablet') {
            $parts = [$colSpanClass($base), 'max-md:col-span-12'];
            if ($sm !== null) {
                $parts[] = 'sm:' . $colSpanClass($sm);
            }
            if ($md !== null && $md !== $base) {
                $parts[] = 'md:' . $colSpanClass($md);
            }
            if ($lg !== null) {
                $parts[] = 'lg:' . $colSpanClass($lg);
            }
            $cellSpanClasses[$i] = implode(' ', $parts) . ' min-w-0';

            continue;
        }

        // mobile (default): base span matches page builder; stack below sm via max-sm:col-span-12 on a 12-col track.
        $parts = [$colSpanClass($base), 'max-sm:col-span-12'];
        if ($sm !== null && $sm !== $base) {
            $parts[] = 'sm:' . $colSpanClass($sm);
        }
        if ($md !== null) {
            $parts[] = 'md:' . $colSpanClass($md);
        }
        if ($lg !== null) {
            $parts[] = 'lg:' . $colSpanClass($lg);
        }
        $cellSpanClasses[$i] = implode(' ', $parts) . ' min-w-0';
    }
@endphp

<div class="grid {{ $twelveGridColsClass }} {{ $gapClass }} {{ $alignmentClass }} mb-6">
    @foreach ($childBuckets as $colIndex => $column)
        <div class="{{ $cellSpanClasses[$colIndex] ?? 'min-w-0' }}">
            @if (!empty($column['items']))
                @foreach ($column['items'] as $item)
                    @include('mksine::page-builder.render.block', ['block' => $item])
                @endforeach
            @else
                <div class="min-h-[50px]"></div>
            @endif
        </div>
    @endforeach
</div>