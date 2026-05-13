@php
    $showcaseAvatars = [
        ['from' => 'from-violet-500', 'to' => 'to-purple-600', 'letter' => 'م'],
        ['from' => 'from-indigo-500', 'to' => 'to-blue-600', 'letter' => 'ر'],
        ['from' => 'from-fuchsia-500', 'to' => 'to-pink-600', 'letter' => 'س'],
        ['from' => 'from-amber-500', 'to' => 'to-orange-600', 'letter' => 'د'],
        ['from' => 'from-emerald-500', 'to' => 'to-teal-600', 'letter' => 'آ'],
    ];
@endphp

<section
    class="home-finance-showcase relative overflow-hidden bg-gradient-to-b from-white via-violet-50/40 to-white py-14 sm:py-20 md:py-24 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950"
    dir="{{ in_array(app()->getLocale(), ['fa', 'ar'], true) ? 'rtl' : 'ltr' }}"
    lang="{{ str_replace('_', '-', app()->getLocale()) }}" aria-labelledby="home-finance-showcase-heading">
    {{-- subtle grid + accents --}}
    <div class="pointer-events-none absolute inset-0 opacity-[0.4] dark:opacity-[0.12]"
        style="background-image: linear-gradient(to right, rgb(148 163 184 / 0.12) 1px, transparent 1px), linear-gradient(to bottom, rgb(148 163 184 / 0.12) 1px, transparent 1px); background-size: 48px 48px;"
        aria-hidden="true"></div>
    <div class="pointer-events-none absolute -top-24 start-[8%] h-40 w-40 rounded-full bg-violet-400/15 blur-3xl dark:bg-violet-600/20"
        aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-20 end-[10%] h-44 w-44 rounded-full bg-orange-400/15 blur-3xl dark:bg-orange-500/15"
        aria-hidden="true"></div>
    <span
        class="pointer-events-none absolute top-24 start-8 text-2xl font-light text-violet-400/50 select-none dark:text-violet-500/30 max-sm:hidden"
        aria-hidden="true">+</span>
    <span
        class="pointer-events-none absolute top-40 end-12 text-2xl font-light text-orange-400/50 select-none dark:text-orange-500/25 max-sm:hidden"
        aria-hidden="true">+</span>
    <span
        class="pointer-events-none absolute bottom-48 start-16 text-xl text-violet-300/40 select-none dark:text-violet-600/20 max-sm:hidden"
        aria-hidden="true">+</span>

    <div class="relative mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <h2 id="home-finance-showcase-heading"
                class="text-balance text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl md:text-5xl lg:text-[3.25rem] lg:leading-tight dark:text-gray-50">
                {{ __('mksine::frontend.home_finance_showcase_heading_prefix') }}<span
                    class="bg-gradient-to-l from-orange-500 via-rose-500 to-violet-600 bg-clip-text text-transparent dark:from-orange-400 dark:via-rose-400 dark:to-violet-400">{{ __('mksine::frontend.home_finance_showcase_heading_accent') }}</span>{{ __('mksine::frontend.home_finance_showcase_heading_suffix') }}
            </h2>

            <div
                class="mt-6 space-y-4 text-pretty text-base leading-relaxed text-gray-600 md:text-lg dark:text-gray-400">
                <p>
                    {{ __('mksine::frontend.home_finance_showcase_p1_before') }}<span
                        class="font-semibold text-violet-600 dark:text-violet-400">{{ __('mksine::frontend.home_finance_showcase_p1_highlight') }}</span>{{ __('mksine::frontend.home_finance_showcase_p1_after') }}
                </p>
                <p>
                    {{ __('mksine::frontend.home_finance_showcase_p2_before') }}<span
                        class="font-semibold text-orange-600 dark:text-orange-400">{{ __('mksine::frontend.home_finance_showcase_p2_highlight') }}</span>{{ __('mksine::frontend.home_finance_showcase_p2_after') }}
                </p>
            </div>

            <p class="mt-10 text-sm font-medium text-gray-500 dark:text-gray-500">
                {{ __('mksine::frontend.home_finance_showcase_social_caption') }}
            </p>
            <div
                class="mx-auto mt-3 inline-flex max-w-full flex-wrap items-center justify-center gap-3 rounded-full border border-white/60 bg-white/70 px-4 py-2.5 shadow-lg shadow-violet-900/5 backdrop-blur-md dark:border-slate-700 dark:bg-slate-800/80 dark:shadow-black/30">
                <span class="text-lg font-bold text-gray-900 tabular-nums dark:text-gray-50">
                    {{ __('mksine::frontend.home_finance_showcase_social_count') }}
                </span>
                <div class="flex items-center ps-1 -space-x-2 rtl:space-x-reverse">
                    @foreach ($showcaseAvatars as $av)
                        <div class="{{ $av['from'] }} {{ $av['to'] }} flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br text-xs font-bold text-white ring-2 ring-white dark:ring-slate-800"
                            aria-hidden="true">{{ $av['letter'] }}</div>
                    @endforeach
                </div>
            </div>
            <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                {{ __('mksine::frontend.home_finance_showcase_trust_line') }}
            </p>

            <div class="mt-10 flex flex-col items-stretch justify-center gap-4 sm:flex-row sm:items-center">
                <a href="#"
                    class="inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-violet-600 to-indigo-600 px-8 py-3.5 text-base font-semibold text-white shadow-lg shadow-violet-600/25 transition hover:from-violet-700 hover:to-indigo-700 hover:shadow-xl hover:shadow-violet-600/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600 dark:shadow-violet-900/40">
                    {{ __('mksine::frontend.home_finance_showcase_cta_primary') }}
                </a>
                <a href="#"
                    class="inline-flex items-center justify-center gap-2 rounded-full border-2 border-gray-200 bg-white/80 px-8 py-3.5 text-base font-semibold text-gray-900 shadow-sm backdrop-blur transition hover:border-violet-300 hover:bg-white hover:shadow-md dark:border-slate-600 dark:bg-slate-800/80 dark:text-gray-100 dark:hover:border-violet-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="h-5 w-5 text-violet-600 dark:text-violet-400" aria-hidden="true">
                        <path
                            d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3">
                        </path>
                    </svg>
                    {{ __('mksine::frontend.home_finance_showcase_cta_secondary') }}
                </a>
            </div>
        </div>

        {{-- layered dashboard preview (no external images) --}}
        <div class="relative mx-auto mt-16 h-[260px] max-w-5xl sm:h-[300px] md:mt-20 md:h-[340px]" aria-hidden="true">
            <div
                class="absolute inset-x-8 bottom-0 h-24 rounded-[50%] bg-gradient-to-t from-violet-200/40 via-transparent to-transparent blur-2xl dark:from-violet-900/30">
            </div>

            {{-- left stack --}}
            <div
                class="absolute start-[2%] top-[18%] z-0 hidden w-[42%] max-w-sm -rotate-6 transform rounded-2xl border border-white/50 bg-white/90 p-3 shadow-2xl shadow-slate-900/10 backdrop-blur-sm md:block dark:border-slate-700 dark:bg-slate-800/95 dark:shadow-black/40">
                <div class="mb-2 flex gap-2">
                    <div class="h-2 w-2 rounded-full bg-red-400"></div>
                    <div class="h-2 w-2 rounded-full bg-amber-400"></div>
                    <div class="h-2 w-2 rounded-full bg-green-400"></div>
                </div>
                <div class="space-y-2">
                    <div class="h-2 w-3/4 rounded bg-slate-200 dark:bg-slate-600"></div>
                    <div
                        class="h-16 rounded-lg bg-gradient-to-r from-violet-100 to-indigo-100 dark:from-violet-900/40 dark:to-indigo-900/40">
                    </div>
                    <div class="flex items-end gap-1">
                        @for ($b = 0; $b < 6; $b++)
                            <div class="h-10 flex-1 rounded bg-violet-200/80 dark:bg-violet-800/50"
                                style="height: {{ 28 + ($b * 6) }}px; margin-top: auto"></div>
                        @endfor
                    </div>
                </div>
            </div>

            {{-- right stack --}}
            <div
                class="absolute end-[2%] top-[18%] z-0 hidden w-[42%] max-w-sm rotate-6 transform rounded-2xl border border-white/50 bg-white/90 p-3 shadow-2xl shadow-slate-900/10 backdrop-blur-sm md:block dark:border-slate-700 dark:bg-slate-800/95 dark:shadow-black/40">
                <div class="mb-2 flex gap-2">
                    <div class="h-2 w-2 rounded-full bg-red-400"></div>
                    <div class="h-2 w-2 rounded-full bg-amber-400"></div>
                    <div class="h-2 w-2 rounded-full bg-green-400"></div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div
                        class="col-span-2 h-20 rounded-lg bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30">
                    </div>
                    @for ($r = 0; $r < 4; $r++)
                        <div class="h-3 rounded bg-slate-200 dark:bg-slate-600"></div>
                    @endfor
                </div>
            </div>

            {{-- center (main) --}}
            <div
                class="relative z-10 mx-auto w-full max-w-lg rounded-3xl border border-white/60 bg-white/95 p-4 shadow-2xl shadow-violet-900/15 backdrop-blur-md sm:p-5 dark:border-slate-600 dark:bg-slate-800/95 dark:shadow-black/50">
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
                        class="col-span-2 flex flex-col justify-between rounded-2xl bg-gradient-to-br from-slate-50 to-violet-50 p-3 dark:from-slate-900/80 dark:to-violet-950/50">
                        <div class="mb-2 text-start text-xs font-semibold text-gray-500 dark:text-gray-400">
                            {{ __('mksine::frontend.home_finance_showcase_mock_revenue') }}
                        </div>
                        <div class="flex items-end gap-1 pt-2">
                            @foreach ([40, 65, 45, 80, 55, 90, 70] as $h)
                                <div class="flex-1 rounded-t bg-gradient-to-t from-violet-500 to-indigo-400 opacity-90 dark:from-violet-600 dark:to-indigo-500"
                                    style="height: {{ $h }}px"></div>
                            @endforeach
                        </div>
                    </div>
                    <div
                        class="flex aspect-square items-center justify-center rounded-2xl bg-gradient-to-br from-orange-50 to-rose-50 dark:from-orange-950/40 dark:to-rose-950/40">
                        <div class="h-20 w-20 rounded-full"
                            style="background: conic-gradient(from 0deg, rgb(139 92 246) 0 40%, rgb(244 63 94) 40% 65%, rgb(16 185 129) 65% 100%)">
                        </div>
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