@php
    $text = $data['text'] ?? '';
    $level = $data['level'] ?? 'h2';
    $alignment = $data['alignment'] ?? 'left';

    $alignmentClasses = match ($alignment) {
        'center' => 'text-center',
        'right' => 'text-end',
        default => 'text-start',
    };

    $levelClasses = match ($level) {
        'h1' => 'text-4xl md:text-5xl lg:text-6xl font-bold tracking-tight',
        'h2' => 'text-3xl md:text-4xl font-bold tracking-tight',
        'h3' => 'text-2xl md:text-3xl font-semibold tracking-tight',
        'h4' => 'text-xl md:text-2xl font-semibold tracking-tight',
        'h5' => 'text-lg md:text-xl font-semibold tracking-tight',
        'h6' => 'text-base md:text-lg font-semibold tracking-tight text-slate-600 dark:text-slate-400',
        default => 'text-2xl md:text-3xl font-bold tracking-tight',
    };
@endphp

<{{ $level }} class="{{ $levelClasses }} {{ $alignmentClasses }} text-balance text-slate-900 dark:text-slate-50 mb-6 md:mb-8">
    {{ $text }}
</{{ $level }}>
