@php
    $text = $data['text'] ?? '';
    $level = $data['level'] ?? 'h2';
    $alignment = $data['alignment'] ?? 'left';
    
    $alignmentClasses = match($alignment) {
        'center' => 'text-center',
        'right' => 'text-end',
        default => 'text-start',
    };
    
    $levelClasses = match($level) {
        'h1' => 'text-4xl md:text-5xl font-bold',
        'h2' => 'text-3xl md:text-4xl font-bold',
        'h3' => 'text-2xl md:text-3xl font-semibold',
        'h4' => 'text-xl md:text-2xl font-semibold',
        'h5' => 'text-lg md:text-xl font-medium',
        'h6' => 'text-base md:text-lg font-medium',
        default => 'text-2xl font-bold',
    };
@endphp

<{{ $level }} class="{{ $levelClasses }} {{ $alignmentClasses }} text-gray-900 dark:text-white mb-4">
    {{ $text }}
</{{ $level }}>
