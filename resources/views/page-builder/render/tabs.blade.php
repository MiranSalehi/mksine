@php
    $style = $data['style'] ?? 'underline';
    $alignment = $data['alignment'] ?? 'left';
    $orientation = $data['orientation'] ?? 'horizontal';
    $tabs = $data['tabs'] ?? [];

    $alignmentClasses = match($alignment) {
        'center' => 'justify-center',
        'right' => 'justify-end',
        'full' => 'w-full',
        default => 'justify-start',
    };

    $tabButtonClasses = match($style) {
        'pills' => 'px-4 py-2 rounded-full text-sm font-medium transition-colors',
        'boxed' => 'px-4 py-2 rounded-t-lg text-sm font-medium transition-colors border-b-2',
        'buttons' => 'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
        default => 'px-4 py-2 text-sm font-medium transition-colors border-b-2',
    };

    $activeClasses = match($style) {
        'pills' => 'bg-pink-500 text-white',
        'boxed' => 'bg-white dark:bg-gray-800 border-pink-500 text-pink-600 dark:text-pink-400',
        'buttons' => 'bg-pink-500 text-white shadow-md',
        default => 'border-pink-500 text-pink-600 dark:text-pink-400',
    };

    $inactiveClasses = match($style) {
        'pills' => 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
        'boxed' => 'border-transparent text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white',
        'buttons' => 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
        default => 'border-transparent text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:border-gray-300',
    };

    $uniqueId = 'tabs-' . uniqid();
@endphp

<section class="mb-8" x-data="{ activeTab: 0 }">
    @if(count($tabs) > 0)
        <div class="{{ $orientation === 'vertical' ? 'flex flex-col sm:flex-row gap-4 sm:gap-8' : '' }}">
            {{-- Tab Headers --}}
            <div class="{{ $orientation === 'vertical' ? 'sm:w-1/4 flex-shrink-0' : '' }}">
                <div
                    class="flex {{ $orientation === 'vertical' ? 'flex-col gap-1' : 'flex-wrap gap-1 ' . $alignmentClasses }} {{ $style === 'underline' || $style === 'boxed' ? 'border-b border-gray-200 dark:border-gray-700' : '' }} {{ $alignment === 'full' && $orientation === 'horizontal' ? '' : '' }}"
                    role="tablist"
                >
                    @foreach($tabs as $index => $tab)
                        @php
                            $icon = $tab['icon'] ?? '';
                            $title = $tab['title'] ?? '';
                        @endphp

                        <button
                            type="button"
                            @click="activeTab = {{ $index }}"
                            :class="activeTab === {{ $index }} ? '{{ $activeClasses }}' : '{{ $inactiveClasses }}'"
                            class="{{ $tabButtonClasses }} {{ $alignment === 'full' ? 'flex-1' : '' }} inline-flex items-center gap-2"
                            role="tab"
                            :aria-selected="activeTab === {{ $index }}"
                        >
                            @if($icon)
                                <x-dynamic-component :component="$icon" class="w-4 h-4" />
                            @endif
                            {{ $title }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Tab Panels --}}
            <div class="{{ $orientation === 'vertical' ? 'flex-1' : 'mt-6' }}">
                @foreach($tabs as $index => $tab)
                    <div
                        x-show="activeTab === {{ $index }}"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        role="tabpanel"
                    >
                        <div class="prose dark:prose-invert max-w-none">
                            {!! mks_render_content($tab['content'] ?? '') !!}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>
