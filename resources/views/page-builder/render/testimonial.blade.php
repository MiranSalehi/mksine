@php
    $heading = $data['heading'] ?? '';
    $layout = $data['layout'] ?? 'grid';
    $columns = $data['columns'] ?? 3;
    $style = $data['style'] ?? 'shadowed';
    $testimonials = $data['testimonials'] ?? [];

    $gridCols = match ($columns) {
        1 => '',
        2 => 'md:grid-cols-2',
        default => 'md:grid-cols-2 lg:grid-cols-3',
    };

    $cardClasses = match ($style) {
        'simple' => 'py-6',
        'bordered' => 'rounded-2xl border border-slate-200/90 bg-white p-6 dark:border-slate-700 dark:bg-slate-900/40',
        'shadowed' => 'rounded-2xl bg-white p-6 shadow-md shadow-slate-900/5 ring-1 ring-slate-900/5 dark:bg-slate-900/50 dark:shadow-black/40 dark:ring-white/10',
        'quote' => 'rounded-2xl border-s-4 border-amber-500 bg-gradient-to-br from-slate-50 to-white p-6 dark:border-amber-400 dark:from-slate-900/60 dark:to-slate-900/30',
        default => 'rounded-2xl bg-white p-6 shadow-md ring-1 ring-slate-900/5 dark:bg-slate-900/50 dark:ring-white/10',
    };
@endphp

<section class="mb-10 md:mb-14">
    @if ($heading)
        <h2 class="mb-10 text-center text-3xl font-bold tracking-tight text-slate-900 sm:mb-12 sm:text-4xl dark:text-slate-50">
            {{ $heading }}
        </h2>
    @endif

    @if (count($testimonials) > 0)
        @if ($layout === 'grid')
            <div class="grid grid-cols-1 gap-6 sm:gap-8 {{ $gridCols }}">
                @foreach ($testimonials as $testimonial)
                    @include('mksine::page-builder.render.partials.testimonial-card', ['testimonial' => $testimonial, 'cardClasses' => $cardClasses, 'style' => $style])
                @endforeach
            </div>
        @elseif ($layout === 'single')
            @if (isset($testimonials[0]))
                <div class="mx-auto max-w-3xl">
                    @include('mksine::page-builder.render.partials.testimonial-card', ['testimonial' => $testimonials[0], 'cardClasses' => $cardClasses.' text-center', 'style' => $style])
                </div>
            @endif
        @else
            <div class="relative overflow-hidden">
                <div class="scrollbar-hide flex gap-6 overflow-x-auto pb-4 snap-x snap-mandatory">
                    @foreach ($testimonials as $testimonial)
                        <div class="w-full flex-shrink-0 snap-start sm:w-1/2 lg:w-1/3">
                            @include('mksine::page-builder.render.partials.testimonial-card', ['testimonial' => $testimonial, 'cardClasses' => $cardClasses, 'style' => $style])
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</section>
