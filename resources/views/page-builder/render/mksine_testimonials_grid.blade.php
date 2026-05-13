@php
    $d = $data ?? [];
    $items = $d['items'] ?? [];
    $td = $d['text_direction'] ?? 'auto';
    $dir = $td === 'rtl' || ($td === 'auto' && in_array(app()->getLocale(), ['fa', 'ar'], true)) ? 'rtl' : 'ltr';
@endphp
<section
    class="home-testimonials relative overflow-hidden border-t border-stone-200/80 bg-stone-50 py-10 sm:py-12 dark:border-slate-800 dark:bg-slate-950"
    dir="{{ $dir }}"
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    aria-labelledby="home-testimonials-heading"
>
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
                    {{ $d['badge'] ?? '' }}
                </div>
                <h2
                    id="home-testimonials-heading"
                    class="text-3xl leading-tight font-bold text-gray-900 sm:text-4xl lg:text-5xl dark:text-gray-50"
                >
                    {{ $d['title_prefix'] ?? '' }}<span
                        class="bg-gradient-to-r from-violet-600 to-indigo-600 bg-clip-text text-transparent dark:from-violet-400 dark:to-indigo-400"
                    >{{ $d['title_accent'] ?? '' }}</span>
                </h2>
                <p
                    class="mt-4 max-w-xl text-pretty text-base leading-relaxed text-gray-600 sm:text-lg dark:text-gray-400"
                >
                    {{ $d['subheading'] ?? '' }}
                </p>
            </div>
            <p
                class="hidden max-w-xs text-sm leading-relaxed text-gray-500 lg:block dark:text-gray-500"
                aria-hidden="true"
            >
                {{ $d['aside'] ?? '' }}
            </p>
        </header>

        <div class="grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-3">
            @foreach ($items as $item)
                @php
                    $g = $item['gradient'] ?? 'from-violet-500 to-purple-600';
                    $initial = \Illuminate\Support\Str::substr(trim((string) ($item['name'] ?? '')), 0, 1);
                    $itemLink = trim((string) ($item['link_url'] ?? ''));
                    if ($itemLink !== '' && preg_match('/^\s*javascript:/i', $itemLink)) {
                        $itemLink = '';
                    }
                    $itemNewTab = (bool) ($item['link_new_tab'] ?? false);
                    $itemIsLink = $itemLink !== '';
                    $itemShellClass =
                        'group relative flex flex-col overflow-hidden rounded-2xl border border-white/40 bg-white/90 p-5 shadow-lg shadow-violet-900/5 backdrop-blur-xl transition duration-300 hover:-translate-y-1 hover:border-violet-200/80 hover:shadow-xl hover:shadow-violet-900/10 sm:p-6 dark:border-slate-700/60 dark:bg-slate-800/95 dark:shadow-black/25 dark:hover:border-violet-600/40'
                        . ($itemIsLink ? ' cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2 dark:focus-visible:ring-violet-400' : '');
                @endphp
                @if ($itemIsLink)
                    <a
                        href="{{ $itemLink }}"
                        @if ($itemNewTab) target="_blank" rel="noopener noreferrer" @endif
                        class="{{ $itemShellClass }} text-inherit no-underline"
                    >
                @else
                    <article class="{{ $itemShellClass }}">
                @endif
                    <div
                        class="pointer-events-none absolute -end-6 -top-6 h-24 w-24 rounded-full bg-gradient-to-br opacity-15 blur-2xl transition group-hover:opacity-25 {{ $g }}"
                        aria-hidden="true"
                    ></div>
                    <div class="relative flex items-start gap-3 sm:gap-4">
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br text-sm font-bold text-white shadow-md sm:h-12 sm:w-12 sm:text-base {{ $g }}"
                            aria-hidden="true"
                        >
                            {{ $initial }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-sm font-bold text-gray-900 sm:text-base dark:text-gray-50">
                                {{ $item['name'] ?? '' }}
                            </h3>
                            <p class="mt-0.5 text-xs text-gray-500 sm:text-sm dark:text-gray-400">
                                {{ $item['city'] ?? '' }}
                            </p>
                        </div>
                    </div>
                    <blockquote class="relative mt-3 flex-1 sm:mt-4">
                        <p class="text-pretty text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                            “{{ $item['quote'] ?? '' }}”
                        </p>
                    </blockquote>
                @if ($itemIsLink)
                    </a>
                @else
                    </article>
                @endif
            @endforeach
        </div>
    </div>
</section>
