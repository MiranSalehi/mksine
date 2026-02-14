@php
    $heading = $data['heading'] ?? '';
    $style = $data['style'] ?? 'bordered';
    $allowMultiple = $data['allow_multiple'] ?? false;
    $firstOpen = $data['first_open'] ?? true;
    $iconPosition = $data['icon_position'] ?? 'right';
    $items = $data['items'] ?? [];

    $containerClasses = match($style) {
        'simple' => 'divide-y divide-gray-200 dark:divide-gray-700',
        'bordered' => 'border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-200 dark:divide-gray-700',
        'separated' => 'space-y-4',
        default => 'border border-gray-200 dark:border-gray-700 rounded-lg divide-y divide-gray-200 dark:divide-gray-700',
    };

    $itemClasses = match($style) {
        'separated' => 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm overflow-hidden',
        default => '',
    };

    $uniqueId = 'accordion-' . uniqid();
@endphp

<section class="mb-8">
    @if($heading)
        <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-6 sm:mb-8">
            {{ $heading }}
        </h2>
    @endif

    @if(count($items) > 0)
        <div
            class="{{ $containerClasses }}"
            x-data="{
                activeIndex: {{ $firstOpen ? '0' : 'null' }},
                allowMultiple: {{ $allowMultiple ? 'true' : 'false' }},
                toggle(index) {
                    if (this.allowMultiple) {
                        this.activeIndex = this.activeIndex === index ? null : index;
                    } else {
                        this.activeIndex = this.activeIndex === index ? null : index;
                    }
                },
                isOpen(index) {
                    return this.activeIndex === index;
                }
            }"
        >
            @foreach($items as $index => $item)
                @php
                    $question = $item['question'] ?? '';
                    $answer = $item['answer'] ?? '';
                @endphp

                <div class="{{ $itemClasses }}">
                    <button
                        type="button"
                        @click="toggle({{ $index }})"
                        class="flex items-center justify-between w-full px-4 sm:px-6 py-4 text-start transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50 {{ $iconPosition === 'left' ? 'flex-row-reverse rtl:flex-row' : 'rtl:flex-row-reverse' }}"
                        :aria-expanded="isOpen({{ $index }})"
                    >
                        <span class="font-medium text-gray-900 dark:text-white pe-4">{{ $question }}</span>
                        <svg
                            class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform duration-200 flex-shrink-0"
                            :class="{ 'rotate-180': isOpen({{ $index }}) }"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div
                        x-show="isOpen({{ $index }})"
                        x-collapse
                        x-cloak
                    >
                        <div class="ps-4 sm:ps-6 pe-4 sm:pe-6 pb-4 prose prose-sm dark:prose-invert max-w-none text-gray-600 dark:text-gray-300">
                            {!! $answer !!}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
