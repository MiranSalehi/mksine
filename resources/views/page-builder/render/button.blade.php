@php
    $text = $data['text'] ?? 'Button';
    $url = $data['url'] ?? '#';
    $style = $data['style'] ?? 'primary';
    $size = $data['size'] ?? 'md';
    $alignment = $data['alignment'] ?? 'left';
    $newTab = $data['new_tab'] ?? false;
    $fullWidth = $data['full_width'] ?? false;
    
    $sizeClasses = match($size) {
        'sm' => 'px-4 py-2 text-sm',
        'md' => 'px-6 py-3 text-base',
        'lg' => 'px-8 py-4 text-lg',
        default => 'px-6 py-3 text-base',
    };
    
    $styleClasses = match($style) {
        'primary' => 'bg-gradient-to-r from-pink-500 to-purple-600 text-white hover:from-pink-600 hover:to-purple-700 shadow-md hover:shadow-lg dark:from-pink-600 dark:to-purple-700 dark:hover:from-pink-500 dark:hover:to-purple-600',
        'secondary' => 'bg-gray-800 text-white hover:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600',
        'outline' => 'bg-transparent border-2 border-pink-500 text-pink-500 hover:bg-pink-500 hover:text-white dark:border-pink-400 dark:text-pink-400 dark:hover:bg-pink-500 dark:hover:text-white',
        'ghost' => 'bg-transparent text-pink-500 hover:bg-pink-50 dark:text-pink-400 dark:hover:bg-pink-900/20',
        default => 'bg-pink-500 text-white hover:bg-pink-600 dark:bg-pink-600 dark:hover:bg-pink-500',
    };
    
    $alignmentClasses = match($alignment) {
        'center' => 'justify-center',
        'right' => 'justify-end',
        default => 'justify-start',
    };
    
    $widthClass = $fullWidth ? 'w-full' : '';
@endphp

<div class="flex {{ $alignmentClasses }} mb-6">
    <a
        href="{{ $url }}"
        @if($newTab) target="_blank" rel="noopener noreferrer" @endif
        class="inline-flex items-center {{ $sizeClasses }} {{ $styleClasses }} {{ $widthClass }} font-semibold rounded-lg transition-all duration-200"
    >
        {{ $text }}
        @if($newTab)
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 ms-2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
            </svg>
        @endif
    </a>
</div>
