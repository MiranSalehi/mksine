@php
    $columns = $data['columns'] ?? 2;
    $layout = $data['layout'] ?? 'equal';
    $gap = $data['gap'] ?? 'md';
    $verticalAlignment = $data['vertical_alignment'] ?? 'start';
    $stackOnMobile = $data['stack_on_mobile'] ?? 'mobile';
    
    $gapClass = match($gap) {
        'none' => 'gap-0',
        'sm' => 'gap-2',
        'md' => 'gap-4',
        'lg' => 'gap-8',
        default => 'gap-4',
    };
    
    $alignmentClass = match($verticalAlignment) {
        'center' => 'items-center',
        'end' => 'items-end',
        'stretch' => 'items-stretch',
        default => 'items-start',
    };
    
    $stackClass = match($stackOnMobile) {
        'always' => '',
        'tablet' => 'md:grid-cols-' . $columns,
        'mobile' => 'sm:grid-cols-' . $columns,
        'never' => 'grid-cols-' . $columns,
        default => 'sm:grid-cols-' . $columns,
    };
    
    // Column width classes based on layout
    $columnWidths = match($layout) {
        '1-2' => ['basis-1/3', 'basis-2/3'],
        '2-1' => ['basis-2/3', 'basis-1/3'],
        '1-3' => ['basis-1/4', 'basis-3/4'],
        '3-1' => ['basis-3/4', 'basis-1/4'],
        '1-2-1' => ['basis-1/4', 'basis-1/2', 'basis-1/4'],
        '2-1-1' => ['basis-1/2', 'basis-1/4', 'basis-1/4'],
        '1-1-2' => ['basis-1/4', 'basis-1/4', 'basis-1/2'],
        default => array_fill(0, $columns, 'flex-1'),
    };
@endphp

<div class="grid grid-cols-1 {{ $stackClass }} {{ $gapClass }} {{ $alignmentClass }} mb-6">
    @if(is_array($children))
        @foreach($children as $colIndex => $column)
            <div class="{{ $columnWidths[$colIndex] ?? 'flex-1' }}">
                @if(!empty($column['items']))
                    @foreach($column['items'] as $item)
                        @include('mksine::page-builder.render.block', ['block' => $item])
                    @endforeach
                @else
                    <div class="min-h-[50px]"></div>
                @endif
            </div>
        @endforeach
    @endif
</div>
