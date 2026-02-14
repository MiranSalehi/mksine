@php
    $size = $data['size'] ?? 'md';
    
    $heightClass = match($size) {
        'xs' => 'h-2',
        'sm' => 'h-4',
        'md' => 'h-8',
        'lg' => 'h-12',
        'xl' => 'h-16',
        '2xl' => 'h-24',
        default => 'h-8',
    };
@endphp

<div class="{{ $heightClass }}"></div>
