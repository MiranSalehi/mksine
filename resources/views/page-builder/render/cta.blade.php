@php
    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $style = $data['style'] ?? 'gradient';
    $alignment = $data['alignment'] ?? 'center';
    $buttonText = $data['button_text'] ?? '';
    $buttonUrl = $data['button_url'] ?? '';
    $showSecondary = $data['show_secondary_button'] ?? false;
    $secondaryText = $data['secondary_button_text'] ?? '';
    $secondaryUrl = $data['secondary_button_url'] ?? '';

    $containerClasses = match($style) {
        'simple' => 'bg-gray-50 dark:bg-gray-800',
        'boxed' => 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg',
        'gradient' => 'bg-gradient-to-r from-pink-500 to-purple-600 text-white',
        'dark' => 'bg-gray-900 text-white',
        default => 'bg-gradient-to-r from-pink-500 to-purple-600 text-white',
    };

    $textColorPrimary = in_array($style, ['gradient', 'dark']) ? 'text-white' : 'text-gray-900 dark:text-white';
    $textColorSecondary = in_array($style, ['gradient', 'dark']) ? 'text-white/80' : 'text-gray-600 dark:text-gray-300';

    $buttonClasses = in_array($style, ['gradient', 'dark'])
        ? 'bg-white text-gray-900 hover:bg-gray-100'
        : 'bg-gradient-to-r from-pink-500 to-purple-600 text-white hover:from-pink-600 hover:to-purple-700';

    $secondaryButtonClasses = in_array($style, ['gradient', 'dark'])
        ? 'bg-transparent border-2 border-white/80 text-white hover:bg-white/10'
        : 'bg-transparent border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700';
@endphp

<section class="rounded-xl p-6 sm:p-8 md:p-12 mb-8 {{ $containerClasses }}">
    <div class="flex flex-col {{ $alignment === 'between' ? 'md:flex-row md:items-center md:justify-between' : '' }} {{ $alignment === 'center' ? 'items-center text-center' : 'text-start' }} gap-6">
        <div class="{{ $alignment === 'between' ? 'md:max-w-2xl' : '' }}">
            @if($title)
                <h2 class="text-2xl sm:text-3xl font-bold {{ $textColorPrimary }} mb-2">
                    {{ $title }}
                </h2>
            @endif

            @if($description)
                <p class="{{ $textColorSecondary }}">
                    {{ $description }}
                </p>
            @endif
        </div>

        @if($buttonText || ($showSecondary && $secondaryText))
            <div class="flex flex-wrap gap-3 {{ $alignment === 'center' ? 'justify-center' : '' }}">
                @if($buttonText && $buttonUrl)
                    <a href="{{ $buttonUrl }}" class="inline-flex items-center px-6 py-3 font-semibold rounded-lg transition-all shadow-md hover:shadow-lg {{ $buttonClasses }}">
                        {{ $buttonText }}
                    </a>
                @endif

                @if($showSecondary && $secondaryText && $secondaryUrl)
                    <a href="{{ $secondaryUrl }}" class="inline-flex items-center px-6 py-3 font-semibold rounded-lg transition-all {{ $secondaryButtonClasses }}">
                        {{ $secondaryText }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</section>
