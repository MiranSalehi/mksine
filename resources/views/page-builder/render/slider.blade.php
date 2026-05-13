@php
    $height = $data['height'] ?? 'medium';
    $autoplay = $data['autoplay'] ?? true;
    $autoplaySpeed = $data['autoplay_speed'] ?? 5000;
    $showArrows = $data['show_arrows'] ?? true;
    $showDots = $data['show_dots'] ?? true;
    $loop = $data['loop'] ?? true;
    $effect = $data['effect'] ?? 'slide';
    $slides = $data['slides'] ?? [];

    $heightClass = match ($height) {
        'small' => 'h-[250px] sm:h-[300px]',
        'medium' => 'h-[350px] sm:h-[400px] md:h-[450px]',
        'large' => 'h-[450px] sm:h-[500px] md:h-[550px]',
        'auto' => 'h-auto',
        default => 'h-[350px] sm:h-[400px] md:h-[450px]',
    };
@endphp

@if (count($slides) > 0)
<section
    class="relative mb-10 overflow-hidden rounded-2xl ring-1 ring-slate-900/10 dark:ring-white/10 {{ $height !== 'auto' ? $heightClass : '' }}"
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
    <div class="relative h-full w-full">
        @foreach ($slides as $index => $slide)
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
                class="absolute inset-0 h-full w-full"
            >
                @if ($imageUrl)
                    <img src="{{ asset($imageUrl) }}" alt="{{ $alt }}" class="h-full w-full object-cover">
                @endif

                @if ($title || $subtitle || $buttonText)
                    <div class="absolute inset-0 flex items-end justify-center bg-gradient-to-t from-slate-950/85 via-slate-950/35 to-transparent pb-10 sm:pb-14">
                        <div class="max-w-2xl px-4 text-center text-white">
                            @if ($title)
                                <h3 class="mb-2 text-2xl font-bold tracking-tight sm:text-3xl md:text-4xl">{{ $title }}</h3>
                            @endif
                            @if ($subtitle)
                                <p class="mb-5 text-base opacity-95 sm:text-lg">{{ $subtitle }}</p>
                            @endif
                            @if ($buttonText && $buttonUrl)
                                <a href="{{ $buttonUrl }}" class="inline-flex items-center rounded-xl bg-amber-400 px-6 py-3 text-sm font-semibold text-slate-900 shadow-lg shadow-black/20 transition hover:bg-amber-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-200 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900">
                                    {{ $buttonText }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if ($showArrows && count($slides) > 1)
        <button
            type="button"
            @click="prev()"
            class="absolute start-3 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-800 shadow-lg backdrop-blur-sm transition hover:bg-white dark:bg-slate-900/90 dark:text-slate-100 dark:hover:bg-slate-800 sm:start-4 sm:h-12 sm:w-12 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
            aria-label="Previous slide"
        >
            <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        <button
            type="button"
            @click="next()"
            class="absolute end-3 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-800 shadow-lg backdrop-blur-sm transition hover:bg-white dark:bg-slate-900/90 dark:text-slate-100 dark:hover:bg-slate-800 sm:end-4 sm:h-12 sm:w-12 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
            aria-label="Next slide"
        >
            <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    @endif

    @if ($showDots && count($slides) > 1)
        <div class="absolute bottom-4 start-1/2 z-10 flex -translate-x-1/2 gap-2">
            @foreach ($slides as $index => $slide)
                <button
                    type="button"
                    @click="goTo({{ $index }})"
                    :class="currentSlide === {{ $index }} ? 'w-8 bg-white' : 'w-2.5 bg-white/45'"
                    class="h-2.5 rounded-full transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900/50"
                    aria-label="Go to slide {{ $index + 1 }}"
                ></button>
            @endforeach
        </div>
    @endif
</section>
@endif
