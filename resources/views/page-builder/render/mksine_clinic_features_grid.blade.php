@php
    $d = $data ?? [];
    $cards = $d['cards'] ?? [];
    $td = $d['text_direction'] ?? 'auto';
    $dir = $td === 'rtl' || ($td === 'auto' && in_array(app()->getLocale(), ['fa', 'ar'], true)) ? 'rtl' : 'ltr';
@endphp
<section
    class="home-clinic-features relative overflow-hidden bg-stone-50 py-10 sm:py-12 dark:bg-slate-950"
    dir="{{ $dir }}"
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    aria-labelledby="home-clinic-features-heading"
>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <header class="mb-12 text-center lg:mb-16">
            <div
                class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/60 px-4 py-2 text-sm font-medium text-indigo-700 shadow-lg backdrop-blur-xl lg:mb-8 lg:px-5 lg:py-2.5 dark:border-slate-600/50 dark:bg-slate-800/80 dark:text-indigo-300 dark:shadow-black/25"
            >
                {{ $d['badge'] ?? '' }}
            </div>
            <h2
                id="home-clinic-features-heading"
                class="mb-6 text-3xl leading-tight font-bold text-gray-900 sm:text-4xl lg:mb-8 lg:text-5xl xl:text-6xl dark:text-gray-50"
            >
                {{ $d['heading_prefix'] ?? '' }}<span
                    class="bg-gradient-to-r from-indigo-600 to-indigo-500 bg-clip-text text-transparent dark:from-indigo-400 dark:to-purple-400"
                >{{ $d['heading_accent'] ?? '' }}</span>
            </h2>
            <p
                class="mx-auto max-w-3xl text-pretty text-lg leading-relaxed text-gray-600 lg:text-xl dark:text-gray-400"
            >
                {{ $d['subheading'] ?? '' }}
            </p>
        </header>
        <div class="grid grid-cols-2 gap-6 lg:grid-cols-3 xl:grid-cols-5">
            @foreach ($cards as $index => $card)
                <article
                    class="group relative cursor-pointer overflow-hidden rounded-2xl border border-transparent bg-white/90 p-6 shadow-lg shadow-gray-900/5 backdrop-blur-xl transition-all duration-700 hover:shadow-xl hover:shadow-gray-900/10 transform-gpu dark:border-slate-700/50 dark:bg-slate-800 dark:shadow-black/30 dark:hover:shadow-black/40"
                    style="animation-delay: {{ $index * 100 }}ms"
                >
                    <div
                        class="absolute inset-0 rounded-2xl bg-gradient-to-br p-px opacity-0 transition-all duration-300 group-hover:opacity-100 {{ $card['gradient'] ?? '' }}"
                    >
                        <div class="h-full w-full rounded-2xl bg-white dark:bg-slate-800"></div>
                    </div>
                    <div class="relative z-10 flex flex-col items-center text-center">
                        <div class="mb-4">
                            <div
                                class="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br shadow-lg transition-all duration-300 group-hover:scale-110 group-hover:rotate-3 {{ $card['gradient'] ?? '' }}"
                            >
                                @include('mksine::themes.mksine.partials.home.clinic-feature-icon', ['name' => $card['icon'] ?? 'sparkles'])
                            </div>
                        </div>
                        <h3
                            class="mb-3 text-lg font-bold text-gray-900 transition-all duration-300 group-hover:bg-gradient-to-r group-hover:from-indigo-600 group-hover:to-purple-600 group-hover:bg-clip-text group-hover:text-transparent dark:text-gray-50 dark:group-hover:from-indigo-400 dark:group-hover:to-purple-400"
                        >
                            {{ $card['title'] ?? '' }}
                        </h3>
                        <p class="text-pretty text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                            {{ $card['body'] ?? '' }}
                        </p>
                        <div
                            class="mt-4 h-0.5 w-0 rounded-full bg-gradient-to-r transition-all duration-500 group-hover:w-full {{ $card['gradient'] ?? '' }}"
                        ></div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
