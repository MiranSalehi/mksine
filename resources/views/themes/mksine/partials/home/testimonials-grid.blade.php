@php
    $testimonialGradients = [
        'from-violet-500 to-purple-600',
        'from-indigo-500 to-blue-600',
        'from-fuchsia-500 to-pink-600',
        'from-amber-500 to-orange-600',
        'from-emerald-500 to-teal-600',
        'from-sky-500 to-indigo-600',
    ];
    $testimonialCount = count($testimonialGradients);
@endphp

<section
    class="home-testimonials relative overflow-hidden border-t border-stone-200/80 bg-stone-50 py-12 sm:py-16 dark:border-slate-800 dark:bg-slate-950"
    dir="{{ in_array(app()->getLocale(), ['fa', 'ar'], true) ? 'rtl' : 'ltr' }}"
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    aria-labelledby="home-testimonials-heading"
    aria-describedby="home-testimonials-subheading"
>
    {{-- soft brand wash --}}
    <div
        class="pointer-events-none absolute inset-x-0 top-0 h-64 bg-gradient-to-b from-violet-200/25 to-transparent dark:from-violet-900/20"
        aria-hidden="true"
    ></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <header class="mb-10 flex flex-col gap-8 lg:mb-14 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-2xl text-center lg:text-start">
                <div
                    class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/60 px-4 py-2 text-sm font-medium text-violet-700 shadow-lg backdrop-blur-xl dark:border-slate-600/50 dark:bg-slate-800/80 dark:text-violet-300 dark:shadow-black/25"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="h-4 w-4 shrink-0 text-violet-600 dark:text-violet-400"
                        aria-hidden="true"
                    >
                        <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"></path>
                    </svg>
                    {{ __('mksine::frontend.home_testimonials_badge') }}
                </div>
                <h2
                    id="home-testimonials-heading"
                    class="text-3xl leading-tight font-bold text-gray-900 sm:text-4xl lg:text-5xl dark:text-gray-50"
                >
                    {{ __('mksine::frontend.home_testimonials_title_prefix') }}<span
                        class="bg-gradient-to-r from-violet-600 to-indigo-600 bg-clip-text text-transparent dark:from-violet-400 dark:to-indigo-400"
                    >{{ __('mksine::frontend.home_testimonials_title_accent') }}</span>
                </h2>
                <p
                    id="home-testimonials-subheading"
                    class="mt-4 max-w-xl text-pretty text-base leading-relaxed text-gray-600 sm:text-lg dark:text-gray-400"
                >
                    {{ __('mksine::frontend.home_testimonials_subheading') }}
                </p>
            </div>
            <p
                class="hidden max-w-xs text-sm leading-relaxed text-gray-500 lg:block dark:text-gray-500"
                aria-hidden="true"
            >
                {{ __('mksine::frontend.home_testimonials_aside') }}
            </p>
        </header>

        <div class="grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-3">
            @for ($i = 1; $i <= $testimonialCount; $i++)
                @php
                    $g = $testimonialGradients[$i - 1];
                @endphp
                <article
                    class="group relative flex flex-col overflow-hidden rounded-2xl border border-white/40 bg-white/90 p-5 shadow-lg shadow-violet-900/5 backdrop-blur-xl transition duration-300 hover:-translate-y-1 hover:border-violet-200/80 hover:shadow-xl hover:shadow-violet-900/10 sm:p-6 dark:border-slate-700/60 dark:bg-slate-800/95 dark:shadow-black/25 dark:hover:border-violet-600/40"
                >
                    <div
                        class="pointer-events-none absolute -end-6 -top-6 h-24 w-24 rounded-full bg-gradient-to-br opacity-15 blur-2xl transition group-hover:opacity-25 {{ $g }}"
                        aria-hidden="true"
                    ></div>
                    <div class="relative flex items-start gap-3 sm:gap-4">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br text-sm font-bold text-white shadow-md sm:h-12 sm:w-12 sm:text-base {{ $g }}"
                            aria-hidden="true"
                        >
                            {{ \Illuminate\Support\Str::substr(trim((string) __('mksine::frontend.home_testimonial_'.$i.'_name')), 0, 1) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-sm font-bold text-gray-900 sm:text-base dark:text-gray-50">
                                {{ __('mksine::frontend.home_testimonial_'.$i.'_name') }}
                            </h3>
                            <p class="mt-0.5 text-xs text-gray-500 sm:text-sm dark:text-gray-400">
                                {{ __('mksine::frontend.home_testimonial_'.$i.'_city') }}
                            </p>
                        </div>
                    </div>
                    <blockquote class="relative mt-3 flex-1 sm:mt-4">
                        <p class="text-pretty text-xs leading-relaxed text-gray-600 sm:text-sm dark:text-gray-300">
                            {{ __('mksine::frontend.home_testimonial_'.$i.'_quote') }}
                        </p>
                    </blockquote>
                    <div
                        class="mt-4 flex gap-0.5 text-violet-500 dark:text-violet-400"
                        aria-label="{{ __('mksine::frontend.home_testimonials_stars_label') }}"
                    >
                        @for ($s = 0; $s < 5; $s++)
                            <svg class="h-3.5 w-3.5 fill-current sm:h-4 sm:w-4" viewBox="0 0 24 24" aria-hidden="true">
                                <path
                                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
                                ></path>
                            </svg>
                        @endfor
                    </div>
                </article>
            @endfor
        </div>
    </div>
</section>
