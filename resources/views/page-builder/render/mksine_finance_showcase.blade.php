@php
    $d = $data ?? [];
    $avatars = $d['avatars'] ?? [];
    $headingId = 'finance-showcase-heading-'.preg_replace('/[^a-zA-Z0-9_-]/', '-', (string) ($blockId ?? 'block'));
    $mockCaption = $d['mock_chart_caption'] ?? 'Revenue';
@endphp
<section
    class="home-finance-showcase relative overflow-visible bg-gradient-to-b from-white via-violet-50/40 to-white py-14 sm:py-20 md:py-24 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950"
    dir="{{ in_array(app()->getLocale(), ['fa', 'ar'], true) ? 'rtl' : 'ltr' }}"
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    aria-labelledby="{{ $headingId }}"
>
    <div
        class="pointer-events-none absolute inset-0 opacity-[0.4] dark:opacity-[0.12]"
        style="background-image: linear-gradient(to right, rgb(148 163 184 / 0.12) 1px, transparent 1px), linear-gradient(to bottom, rgb(148 163 184 / 0.12) 1px, transparent 1px); background-size: 48px 48px;"
        aria-hidden="true"
    ></div>
    <div
        class="pointer-events-none absolute -top-24 start-[8%] h-40 w-40 rounded-full bg-violet-400/15 blur-3xl dark:bg-violet-600/20"
        aria-hidden="true"
    ></div>
    <div
        class="pointer-events-none absolute -bottom-20 end-[10%] h-44 w-44 rounded-full bg-orange-400/15 blur-3xl dark:bg-orange-500/15"
        aria-hidden="true"
    ></div>

    <div class="relative mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <h2
                id="{{ $headingId }}"
                class="text-balance text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl md:text-5xl lg:text-[3.25rem] lg:leading-tight dark:text-gray-50"
            >
                {{ $d['heading_prefix'] ?? '' }}<span
                    class="bg-gradient-to-l from-orange-500 via-rose-500 to-violet-600 bg-clip-text text-transparent dark:from-orange-400 dark:via-rose-400 dark:to-violet-400"
                >{{ $d['heading_accent'] ?? '' }}</span>{{ $d['heading_suffix'] ?? '' }}
            </h2>

            <div
                class="mt-6 space-y-4 text-pretty text-base leading-relaxed text-gray-600 md:text-lg dark:text-gray-400"
            >
                <p>
                    {{ $d['p1_before'] ?? '' }}<span
                        class="font-semibold text-violet-600 dark:text-violet-400"
                    >{{ $d['p1_highlight'] ?? '' }}</span>{{ $d['p1_after'] ?? '' }}
                </p>
                <p>
                    {{ $d['p2_before'] ?? '' }}<span
                        class="font-semibold text-orange-600 dark:text-orange-400"
                    >{{ $d['p2_highlight'] ?? '' }}</span>{{ $d['p2_after'] ?? '' }}
                </p>
            </div>

            <p class="mt-10 text-sm font-medium text-gray-500 dark:text-gray-500">
                {{ $d['social_caption'] ?? '' }}
            </p>
            <div
                class="mx-auto mt-3 inline-flex max-w-full flex-wrap items-center justify-center gap-3 rounded-full border border-white/60 bg-white/70 px-4 py-2.5 shadow-lg shadow-violet-900/5 backdrop-blur-md dark:border-slate-700 dark:bg-slate-800/80 dark:shadow-black/30"
            >
                <span class="text-lg font-bold text-gray-900 tabular-nums dark:text-gray-50">
                    {{ $d['social_count'] ?? '' }}
                </span>
                <div class="flex items-center ps-1 -space-x-2 rtl:space-x-reverse">
                    @foreach ($avatars as $av)
                        <div
                            class="{{ ($av['from'] ?? '') }} {{ ($av['to'] ?? '') }} flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br text-xs font-bold text-white ring-2 ring-white dark:ring-slate-800"
                            aria-hidden="true"
                        >{{ $av['letter'] ?? '' }}</div>
                    @endforeach
                </div>
            </div>
            <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                {{ $d['trust_line'] ?? '' }}
            </p>

            <div class="mt-10 flex flex-col items-stretch justify-center gap-4 sm:flex-row sm:items-center">
                <a
                    href="{{ $d['cta_primary_url'] ?? '#' }}"
                    class="inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-violet-600 to-indigo-600 px-8 py-3.5 text-base font-semibold text-white shadow-lg shadow-violet-600/25 transition hover:from-violet-700 hover:to-indigo-700 hover:shadow-xl hover:shadow-violet-600/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600 dark:shadow-violet-900/40"
                >
                    {{ $d['cta_primary_label'] ?? '' }}
                </a>
                <a
                    href="{{ $d['cta_secondary_url'] ?? '#' }}"
                    class="inline-flex items-center justify-center gap-2 rounded-full border-2 border-gray-200 bg-white/80 px-8 py-3.5 text-base font-semibold text-gray-900 shadow-sm backdrop-blur transition hover:border-violet-300 hover:bg-white hover:shadow-md dark:border-slate-600 dark:bg-slate-800/80 dark:text-gray-100 dark:hover:border-violet-500"
                >
                    {{ $d['cta_secondary_label'] ?? '' }}
                </a>
            </div>
        </div>

        {{-- Three-layer mock: rear left + rear right + front (matches theme layout; overflow-visible on section so rotation is not clipped) --}}
        <div
            class="relative mx-auto mt-16 min-h-[280px] max-w-5xl pb-8 sm:min-h-[300px] md:mt-20 md:min-h-[320px]"
            aria-hidden="true"
        >
            <div
                class="pointer-events-none absolute inset-x-4 bottom-0 h-24 rounded-[50%] bg-gradient-to-t from-violet-200/40 via-transparent to-transparent blur-2xl dark:from-violet-900/30 sm:inset-x-8"
            ></div>

            <div
                class="absolute -start-2 top-[10%] z-0 w-[40%] max-w-[200px] -rotate-6 rounded-2xl border border-white/50 bg-white/90 p-2 shadow-2xl shadow-slate-900/10 backdrop-blur-sm sm:start-[2%] sm:max-w-[240px] sm:p-3 md:top-[18%] md:max-w-sm dark:border-slate-700 dark:bg-slate-800/95 dark:shadow-black/40"
            >
                <div class="mb-2 flex gap-1.5 sm:gap-2">
                    <div class="h-2 w-2 rounded-full bg-red-400"></div>
                    <div class="h-2 w-2 rounded-full bg-amber-400"></div>
                    <div class="h-2 w-2 rounded-full bg-green-400"></div>
                </div>
                <div class="space-y-2">
                    <div class="h-2 w-3/4 rounded bg-slate-200 dark:bg-slate-600"></div>
                    <div
                        class="h-12 rounded-lg bg-gradient-to-r from-violet-100 to-indigo-100 sm:h-16 dark:from-violet-900/40 dark:to-indigo-900/40"
                    ></div>
                    <div class="flex items-end gap-0.5 sm:gap-1">
                        @for ($b = 0; $b < 6; $b++)
                            <div
                                class="min-w-0 flex-1 rounded bg-violet-200/80 dark:bg-violet-800/50"
                                style="height: {{ 22 + ($b * 5) }}px; margin-top: auto"
                            ></div>
                        @endfor
                    </div>
                </div>
            </div>

            <div
                class="absolute -end-2 top-[10%] z-0 w-[40%] max-w-[200px] rotate-6 rounded-2xl border border-white/50 bg-white/90 p-2 shadow-2xl shadow-slate-900/10 backdrop-blur-sm sm:end-[2%] sm:max-w-[240px] sm:p-3 md:top-[18%] md:max-w-sm dark:border-slate-700 dark:bg-slate-800/95 dark:shadow-black/40"
            >
                <div class="mb-2 flex gap-1.5 sm:gap-2">
                    <div class="h-2 w-2 rounded-full bg-red-400"></div>
                    <div class="h-2 w-2 rounded-full bg-amber-400"></div>
                    <div class="h-2 w-2 rounded-full bg-green-400"></div>
                </div>
                <div class="grid grid-cols-2 gap-1.5 sm:gap-2">
                    <div
                        class="col-span-2 h-14 rounded-lg bg-gradient-to-br from-emerald-100 to-teal-100 sm:h-20 dark:from-emerald-900/30 dark:to-teal-900/30"
                    ></div>
                    @for ($r = 0; $r < 4; $r++)
                        <div class="h-2.5 rounded bg-slate-200 sm:h-3 dark:bg-slate-600"></div>
                    @endfor
                </div>
            </div>

            <div
                class="relative z-10 mx-auto w-[92%] max-w-lg rounded-3xl border border-white/60 bg-white/95 p-4 shadow-2xl shadow-violet-900/15 backdrop-blur-md sm:w-full sm:p-5 dark:border-slate-600 dark:bg-slate-800/95 dark:shadow-black/50"
            >
                <div class="mb-3 flex items-center justify-between gap-2">
                    <div class="flex gap-2">
                        <div class="h-2.5 w-2.5 rounded-full bg-red-400"></div>
                        <div class="h-2.5 w-2.5 rounded-full bg-amber-400"></div>
                        <div class="h-2.5 w-2.5 rounded-full bg-green-400"></div>
                    </div>
                    <div class="h-2 w-24 rounded-full bg-slate-200 dark:bg-slate-600"></div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div
                        class="col-span-2 flex flex-col justify-between rounded-2xl bg-gradient-to-br from-slate-50 to-violet-50 p-3 dark:from-slate-900/80 dark:to-violet-950/50"
                    >
                        <div class="mb-2 text-start text-xs font-semibold text-gray-500 dark:text-gray-400">
                            {{ $mockCaption }}
                        </div>
                        <div class="flex items-end gap-1 pt-2">
                            @foreach ([40, 65, 45, 80, 55, 90, 70] as $h)
                                <div
                                    class="min-w-0 flex-1 rounded-t bg-gradient-to-t from-violet-500 to-indigo-400 opacity-90 dark:from-violet-600 dark:to-indigo-500"
                                    style="height: {{ $h }}px"
                                ></div>
                            @endforeach
                        </div>
                    </div>
                    <div
                        class="flex aspect-square items-center justify-center rounded-2xl bg-gradient-to-br from-orange-50 to-rose-50 dark:from-orange-950/40 dark:to-rose-950/40"
                    >
                        <div
                            class="h-16 w-16 rounded-full sm:h-20 sm:w-20"
                            style="background: conic-gradient(from 0deg, rgb(139 92 246) 0 40%, rgb(244 63 94) 40% 65%, rgb(16 185 129) 65% 100%)"
                        ></div>
                    </div>
                </div>
                <div class="mt-3 grid grid-cols-3 gap-2 border-t border-slate-100 pt-3 dark:border-slate-700">
                    @for ($t = 0; $t < 3; $t++)
                        <div class="rounded-lg bg-slate-100/80 p-2 dark:bg-slate-700/50">
                            <div class="mb-1 h-1.5 w-2/3 rounded bg-slate-300 dark:bg-slate-500"></div>
                            <div class="h-2 w-full rounded bg-slate-200 dark:bg-slate-600"></div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</section>
