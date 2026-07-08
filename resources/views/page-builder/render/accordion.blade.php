@php
    $heading = $data['heading'] ?? '';
    $style = $data['style'] ?? 'bordered';
    $allowMultiple = $data['allow_multiple'] ?? false;
    $firstOpen = $data['first_open'] ?? true;
    $iconPosition = $data['icon_position'] ?? 'right';
    $items = $data['items'] ?? [];

    $containerClasses = match ($style) {
        'simple' => 'divide-y divide-slate-200 dark:divide-slate-700/80',
        'bordered' => 'divide-y divide-slate-200 overflow-hidden rounded-2xl border border-slate-200/90 bg-white ring-1 ring-slate-900/5 dark:divide-slate-700 dark:border-slate-700 dark:bg-slate-900/30 dark:ring-white/10',
        'separated' => 'space-y-4',
        default => 'divide-y divide-slate-200 overflow-hidden rounded-2xl border border-slate-200/90 bg-white ring-1 ring-slate-900/5 dark:divide-slate-700 dark:border-slate-700 dark:bg-slate-900/30 dark:ring-white/10',
    };

    $itemClasses = match ($style) {
        'separated' => 'overflow-hidden rounded-2xl border border-slate-200/90 bg-white shadow-sm ring-1 ring-slate-900/5 dark:border-slate-700 dark:bg-slate-900/40 dark:ring-white/10',
        default => '',
    };
@endphp

<section class="mb-10 md:mb-14">
    @if ($heading)
        <h2 class="mb-8 text-3xl font-bold tracking-tight text-slate-900 sm:mb-10 sm:text-4xl dark:text-slate-50">
            {{ $heading }}
        </h2>
    @endif

    @if (count($items) > 0)
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
            @foreach ($items as $index => $item)
                @php
                    $question = $item['question'] ?? '';
                    $answer = $item['answer'] ?? '';
                @endphp

                <div class="{{ $itemClasses }}">
                    <button
                        type="button"
                        @click="toggle({{ $index }})"
                        class="{{ $iconPosition === 'left' ? 'flex-row-reverse rtl:flex-row' : 'rtl:flex-row-reverse' }} flex w-full items-center justify-between gap-4 px-4 py-4 text-start transition-colors hover:bg-slate-50 sm:px-6 sm:py-5 dark:hover:bg-slate-800/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-amber-500/80"
                        :aria-expanded="isOpen({{ $index }})"
                    >
                        <span class="pe-2 text-base font-semibold text-slate-900 dark:text-slate-50">{{ $question }}</span>
                        <svg
                            class="h-5 w-5 flex-shrink-0 text-amber-600 transition-transform duration-200 dark:text-amber-400"
                            :class="{ 'rotate-180': isOpen({{ $index }}) }"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <div
                        x-show="isOpen({{ $index }})"
                        x-collapse
                        x-cloak
                    >
                        <div class="border-t border-slate-100 px-4 pb-5 ps-4 pe-4 pt-4 text-sm leading-relaxed text-slate-600 dark:border-slate-800 dark:text-slate-300 sm:px-6 sm:pb-6 sm:ps-6 sm:pe-6 prose prose-sm dark:prose-invert max-w-none">
                            {!! mks_render_content($answer) !!}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
