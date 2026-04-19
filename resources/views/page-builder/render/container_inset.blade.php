@php
    $data = is_array($data ?? null) ? $data : [];
    $padding = $data['padding_inline'] ?? 'md';
    $maxWidth = $data['max_width'] ?? 'full';

    $paddingClass = match ($padding) {
        'none' => 'px-0',
        'xs' => 'px-3 sm:px-4',
        'sm' => 'px-4 sm:px-5',
        'md' => 'px-4 sm:px-6 lg:px-8',
        'lg' => 'px-6 sm:px-8 lg:px-10',
        'xl' => 'px-6 sm:px-10 lg:px-12',
        '2xl' => 'px-8 sm:px-12 lg:px-16',
        default => 'px-4 sm:px-6 lg:px-8',
    };

    $widthClass = match ($maxWidth) {
        'prose' => 'max-w-prose w-full mx-auto',
        '3xl' => 'max-w-3xl w-full mx-auto',
        '5xl' => 'max-w-5xl w-full mx-auto',
        '6xl' => 'max-w-6xl w-full mx-auto',
        '7xl' => 'max-w-7xl w-full mx-auto',
        default => 'w-full max-w-none',
    };

    $items = [];
    if (isset($children[0]) && is_array($children[0]) && ! empty($children[0]['items']) && is_array($children[0]['items'])) {
        $items = $children[0]['items'];
    }
@endphp
<div class="mksine-container-inset {{ $paddingClass }} {{ $widthClass }}">
    @foreach ($items as $item)
        @include('mksine::page-builder.render.block', ['block' => $item])
    @endforeach
</div>
