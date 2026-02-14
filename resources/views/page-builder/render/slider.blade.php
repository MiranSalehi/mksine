@php
    $type = $data['type'] ?? 'image';
    $height = $data['height'] ?? 'medium';
    $autoplay = $data['autoplay'] ?? true;
    $autoplaySpeed = $data['autoplay_speed'] ?? 5000;
    $showArrows = $data['show_arrows'] ?? true;
    $showDots = $data['show_dots'] ?? true;
    $loop = $data['loop'] ?? true;
    $effect = $data['effect'] ?? 'slide';
    $slides = $data['slides'] ?? [];

    $heightClass = match($height) {
        'small' => 'h-[250px] sm:h-[300px]',
        'medium' => 'h-[350px] sm:h-[400px] md:h-[450px]',
        'large' => 'h-[450px] sm:h-[500px] md:h-[550px]',
        'auto' => 'h-auto',
        default => 'h-[350px] sm:h-[400px] md:h-[450px]',
    };

    $uniqueId = 'slider-' . uniqid();
@endphp

@if(count($slides) > 0)
<section
    class="mb-8 relative overflow-hidden rounded-xl {{ $height !== 'auto' ? $heightClass : '' }}"
    x-data="{
        currentSlide: 0,
        totalSlides: {{ count($slides) }},
        autoplay: {{ $autoplay ? 'true' : 'false' }},
        autoplaySpeed: {{ $autoplaySpeed }},
        loop: {{ $loop ? 'true' : 'false' }},
        interval: null,
        init() {
            if (this.autoplay) {
                this.startAutoplay();
            }
        },
        startAutoplay() {
            this.interval = setInterval(() => {
                this.next();
            }, this.autoplaySpeed);
        },
        stopAutoplay() {
            if (this.interval) {
                clearInterval(this.interval);
            }
        },
        next() {
            if (this.currentSlide < this.totalSlides - 1) {
                this.currentSlide++;
            } else if (this.loop) {
                this.currentSlide = 0;
            }
        },
        prev() {
            if (this.currentSlide > 0) {
                this.currentSlide--;
            } else if (this.loop) {
                this.currentSlide = this.totalSlides - 1;
            }
        },
        goTo(index) {
            this.currentSlide = index;
        }
    }"
    @mouseenter="stopAutoplay()"
    @mouseleave="autoplay && startAutoplay()"
>
    {{-- Slides Container --}}
    <div class="relative w-full h-full">
        @foreach($slides as $index => $slide)
            @php
                $imageId = $slide['image'] ?? null;
                $title = $slide['title'] ?? '';
                $subtitle = $slide['subtitle'] ?? '';
                $buttonText = $slide['button_text'] ?? '';
                $buttonUrl = $slide['button_url'] ?? '';
                $alt = $slide['alt'] ?? $title;

                $imageUrl = null;
                if ($imageId) {
                    $media = \Miran\Mksine\Models\Media::find($imageId);
                    $imageUrl = $media?->url;
                }
            @endphp

            <div
                x-show="currentSlide === {{ $index }}"
                x-transition:enter="{{ $effect === 'fade' ? 'transition-opacity duration-500' : 'transition-transform duration-500' }}"
                x-transition:enter-start="{{ $effect === 'fade' ? 'opacity-0' : 'translate-x-full' }}"
                x-transition:enter-end="{{ $effect === 'fade' ? 'opacity-100' : 'translate-x-0' }}"
                x-transition:leave="{{ $effect === 'fade' ? 'transition-opacity duration-500' : 'transition-transform duration-500' }}"
                x-transition:leave-start="{{ $effect === 'fade' ? 'opacity-100' : 'translate-x-0' }}"
                x-transition:leave-end="{{ $effect === 'fade' ? 'opacity-0' : '-translate-x-full' }}"
                class="absolute inset-0 w-full h-full"
            >
                @if($imageUrl)
                    <img src="{{ asset($imageUrl) }}" alt="{{ $alt }}" class="w-full h-full object-cover">
                @endif

                {{-- Overlay and Content --}}
                @if($title || $subtitle || $buttonText)
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent flex items-end justify-center pb-12 sm:pb-16">
                        <div class="text-center text-white px-4 max-w-2xl">
                            @if($title)
                                <h3 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-2">{{ $title }}</h3>
                            @endif
                            @if($subtitle)
                                <p class="text-base sm:text-lg opacity-90 mb-4">{{ $subtitle }}</p>
                            @endif
                            @if($buttonText && $buttonUrl)
                                <a href="{{ $buttonUrl }}" class="inline-flex items-center px-6 py-3 bg-white text-gray-900 font-semibold rounded-lg hover:bg-gray-100 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-gray-200 transition-colors">
                                    {{ $buttonText }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Navigation Arrows --}}
    @if($showArrows && count($slides) > 1)
        <button
            type="button"
            @click="prev()"
            class="absolute start-2 sm:start-4 top-1/2 -translate-y-1/2 w-10 h-10 sm:w-12 sm:h-12 bg-white/80 hover:bg-white dark:bg-gray-800/80 dark:hover:bg-gray-700 rounded-full flex items-center justify-center shadow-lg transition-all z-10"
        >
            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-800 dark:text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        <button
            type="button"
            @click="next()"
            class="absolute end-2 sm:end-4 top-1/2 -translate-y-1/2 w-10 h-10 sm:w-12 sm:h-12 bg-white/80 hover:bg-white dark:bg-gray-800/80 dark:hover:bg-gray-700 rounded-full flex items-center justify-center shadow-lg transition-all z-10"
        >
            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-800 dark:text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    @endif

    {{-- Dots/Indicators --}}
    @if($showDots && count($slides) > 1)
        <div class="absolute bottom-4 start-1/2 -translate-x-1/2 flex gap-2 z-10">
            @foreach($slides as $index => $slide)
                <button
                    type="button"
                    @click="goTo({{ $index }})"
                    :class="currentSlide === {{ $index }} ? 'bg-white w-8' : 'bg-white/50 w-3'"
                    class="h-3 rounded-full transition-all duration-300"
                ></button>
            @endforeach
        </div>
    @endif
</section>
@endif
