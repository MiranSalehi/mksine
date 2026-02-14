@php
    $heading = $data['heading'] ?? '';
    $layout = $data['layout'] ?? 'grid';
    $columns = $data['columns'] ?? 3;
    $style = $data['style'] ?? 'shadowed';
    $testimonials = $data['testimonials'] ?? [];

    $gridCols = match($columns) {
        1 => '',
        2 => 'md:grid-cols-2',
        default => 'md:grid-cols-2 lg:grid-cols-3',
    };

    $cardClasses = match($style) {
        'simple' => 'py-6',
        'bordered' => 'p-6 border border-gray-200 dark:border-gray-700 rounded-xl',
        'shadowed' => 'p-6 bg-white dark:bg-gray-800 shadow-lg rounded-xl',
        'quote' => 'p-6 bg-gray-50 dark:bg-gray-800 rounded-xl border-s-4 border-pink-500 dark:border-pink-400',
        default => 'p-6 bg-white dark:bg-gray-800 shadow-lg rounded-xl',
    };
@endphp

<section class="mb-8">
    @if($heading)
        <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 dark:text-white text-center mb-8 sm:mb-12">
            {{ $heading }}
        </h2>
    @endif

    @if(count($testimonials) > 0)
        @if($layout === 'grid')
            <div class="grid grid-cols-1 {{ $gridCols }} gap-6 sm:gap-8">
                @foreach($testimonials as $testimonial)
                    @include('mksine::page-builder.render.partials.testimonial-card', ['testimonial' => $testimonial, 'cardClasses' => $cardClasses, 'style' => $style])
                @endforeach
            </div>
        @elseif($layout === 'single')
            @if(isset($testimonials[0]))
                <div class="max-w-3xl mx-auto">
                    @include('mksine::page-builder.render.partials.testimonial-card', ['testimonial' => $testimonials[0], 'cardClasses' => $cardClasses . ' text-center', 'style' => $style])
                </div>
            @endif
        @else
            {{-- Carousel layout - simplified version --}}
            <div class="relative overflow-hidden">
                <div class="flex gap-6 overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-hide">
                    @foreach($testimonials as $testimonial)
                        <div class="flex-shrink-0 w-full sm:w-1/2 lg:w-1/3 snap-start">
                            @include('mksine::page-builder.render.partials.testimonial-card', ['testimonial' => $testimonial, 'cardClasses' => $cardClasses, 'style' => $style])
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</section>
