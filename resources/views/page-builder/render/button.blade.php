@php
    $text = $data['text'] ?? 'Button';
    $url = $data['url'] ?? '#';
    $style = $data['style'] ?? 'primary';
    $size = $data['size'] ?? 'md';
    $alignment = $data['alignment'] ?? 'left';
    $newTab = $data['new_tab'] ?? false;
    $fullWidth = $data['full_width'] ?? false;

    $sizeClasses = match ($size) {
        'sm' => 'px-4 py-2 text-sm gap-1.5',
        'md' => 'px-6 py-2.5 text-sm sm:text-base gap-2',
        'lg' => 'px-8 py-3.5 text-base sm:text-lg gap-2',
        default => 'px-6 py-2.5 text-sm sm:text-base gap-2',
    };

    $styleClasses = match ($style) {
        'primary' => 'bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-md shadow-amber-900/20 hover:from-amber-600 hover:to-orange-700 hover:shadow-lg dark:from-amber-500 dark:to-orange-600 dark:shadow-black/30',
        'secondary' => 'bg-slate-900 text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white',
        'outline' => 'border-2 border-amber-500/90 bg-transparent text-amber-700 hover:bg-amber-50 dark:border-amber-400 dark:text-amber-300 dark:hover:bg-amber-950/40',
        'ghost' => 'bg-transparent text-amber-700 hover:bg-amber-500/10 dark:text-amber-300 dark:hover:bg-amber-400/10',
        default => 'bg-amber-500 text-white shadow-md hover:bg-amber-600',
    };

    $alignmentClasses = match ($alignment) {
        'center' => 'justify-center',
        'right' => 'justify-end',
        default => 'justify-start',
    };

    $widthClass = $fullWidth ? 'w-full justify-center' : '';
@endphp

<div class="mb-8 flex {{ $alignmentClasses }}">
    <a
        href="{{ $url }}"
        @if ($newTab) target="_blank" rel="noopener noreferrer" @endif
        class="inline-flex items-center justify-center font-semibold {{ $sizeClasses }} {{ $styleClasses }} {{ $widthClass }} rounded-xl transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
    >
        {{ $text }}
        @if ($newTab)
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 opacity-90">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
            </svg>
        @endif
    </a>
</div>
