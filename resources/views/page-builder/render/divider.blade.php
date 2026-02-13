@php
    $style = $data['style'] ?? 'solid';
    $width = $data['width'] ?? '100';
    $alignment = $data['alignment'] ?? 'center';
    
    $styleClass = match($style) {
        'dashed' => 'border-dashed',
        'dotted' => 'border-dotted',
        default => 'border-solid',
    };
    
    $widthClass = match($width) {
        '25' => 'w-1/4',
        '50' => 'w-1/2',
        '75' => 'w-3/4',
        default => 'w-full',
    };
    
    $alignmentClass = match($alignment) {
        'left' => '',
        'center' => 'mx-auto',
        'right' => 'ms-auto',
        default => 'mx-auto',
    };
@endphp

<hr class="border-t border-gray-300 dark:border-gray-600 {{ $styleClass }} {{ $widthClass }} {{ $alignmentClass }} my-6">
