@php
    $heading = $data['heading'] ?? '';
    $subheading = $data['subheading'] ?? '';
    $columns = $data['columns'] ?? 3;
    $style = $data['style'] ?? 'simple';
    $iconStyle = $data['icon_style'] ?? 'circle';
    $features = $data['features'] ?? [];

    $gridCols = match($columns) {
        2 => 'sm:grid-cols-2',
        4 => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };

    $cardClasses = match($style) {
        'bordered' => 'border border-gray-200 dark:border-gray-700 rounded-lg',
        'shadowed' => 'bg-white dark:bg-gray-800 shadow-lg rounded-lg',
        'filled' => 'bg-gray-50 dark:bg-gray-800 rounded-lg',
        default => '',
    };

    $iconBgClasses = match($iconStyle) {
        'circle' => 'w-14 h-14 rounded-full bg-gradient-to-br from-pink-100 to-purple-100 dark:from-pink-900/30 dark:to-purple-900/30',
        'square' => 'w-14 h-14 rounded-lg bg-gradient-to-br from-pink-100 to-purple-100 dark:from-pink-900/30 dark:to-purple-900/30',
        default => '',
    };
@endphp

<section class="mb-8">
    @if($heading || $subheading)
        <div class="text-center mb-8 sm:mb-12">
            @if($heading)
                <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    {{ $heading }}
                </h2>
            @endif

            @if($subheading)
                <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                    {{ $subheading }}
                </p>
            @endif
        </div>
    @endif

    @if(count($features) > 0)
        <div class="grid grid-cols-1 {{ $gridCols }} gap-6 sm:gap-8">
            @foreach($features as $feature)
                @php
                    $icon = $feature['icon'] ?? 'heroicon-o-star';
                    $title = $feature['title'] ?? '';
                    $description = $feature['description'] ?? '';
                    $link = $feature['link'] ?? '';
                @endphp

                <div class="{{ $cardClasses }} {{ $style !== 'simple' ? 'p-6' : 'py-4' }}">
                    @if($icon && $iconStyle !== 'none')
                        <div class="{{ $iconBgClasses }} flex items-center justify-center mb-4">
                            <x-dynamic-component :component="$icon" class="w-7 h-7 text-pink-600 dark:text-pink-400" />
                        </div>
                    @endif

                    @if($title)
                        @if($link)
                            <a href="{{ $link }}" class="block">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 hover:text-pink-600 dark:hover:text-pink-400 transition-colors">
                                    {{ $title }}
                                </h3>
                            </a>
                        @else
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                {{ $title }}
                            </h3>
                        @endif
                    @endif

                    @if($description)
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            {{ $description }}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</section>
