@php
    $siteName = mks_setting('site_name') ?: config('app.name', 'MKSine');
@endphp

<section
    class="home-placeholder relative overflow-hidden bg-gradient-to-b from-violet-50/80 via-white to-white dark:from-slate-950 dark:via-slate-950 dark:to-slate-900"
    aria-labelledby="home-placeholder-heading"
>
    <div class="pointer-events-none absolute inset-x-0 top-0 h-72 bg-[radial-gradient(ellipse_at_top,_rgba(139,92,246,0.18),_transparent_60%)] dark:bg-[radial-gradient(ellipse_at_top,_rgba(139,92,246,0.12),_transparent_60%)]" aria-hidden="true"></div>

    <div class="relative mx-auto max-w-6xl px-6 py-16 sm:py-20 lg:px-10 lg:py-24">
        <div class="mx-auto max-w-3xl text-center">
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-violet-600 dark:text-violet-400">
                {{ __('mksine::frontend.home_placeholder_eyebrow') }}
            </p>
            <h1 id="home-placeholder-heading" class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl dark:text-white">
                {{ __('mksine::frontend.home_placeholder_title', ['site' => $siteName]) }}
            </h1>
            <p class="mx-auto mt-5 max-w-2xl text-base leading-relaxed text-slate-600 sm:text-lg dark:text-slate-300">
                {{ __('mksine::frontend.home_placeholder_lead') }}
            </p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a
                    href="{{ url('/admin') }}"
                    class="inline-flex items-center justify-center rounded-xl bg-violet-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-violet-600/20 transition hover:bg-violet-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-violet-600"
                >
                    {{ __('mksine::frontend.home_placeholder_admin_cta') }}
                </a>
                <span class="text-sm text-slate-500 dark:text-slate-400">
                    {{ __('mksine::frontend.home_placeholder_admin_hint') }}
                </span>
            </div>
        </div>

        <div class="mt-14 space-y-6 lg:mt-16">
            <div
                class="home-placeholder-zone rounded-3xl border border-dashed border-violet-200/80 bg-white/70 p-8 shadow-sm backdrop-blur-sm dark:border-violet-500/20 dark:bg-slate-900/50"
                aria-label="{{ __('mksine::frontend.home_placeholder_hero_label') }}"
            >
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center">
                    <div class="flex-1 space-y-4">
                        <div class="h-3 w-28 rounded-full bg-violet-100 dark:bg-violet-500/20"></div>
                        <div class="h-8 max-w-xl rounded-2xl bg-slate-100 dark:bg-slate-800"></div>
                        <div class="h-4 max-w-2xl rounded-xl bg-slate-100 dark:bg-slate-800"></div>
                        <div class="h-4 max-w-lg rounded-xl bg-slate-100 dark:bg-slate-800"></div>
                        <div class="flex gap-3 pt-2">
                            <div class="h-10 w-32 rounded-xl bg-violet-100 dark:bg-violet-500/20"></div>
                            <div class="h-10 w-28 rounded-xl bg-slate-100 dark:bg-slate-800"></div>
                        </div>
                    </div>
                    <div class="flex h-48 w-full max-w-md items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-slate-50 text-sm font-medium text-slate-400 lg:h-56 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-500">
                        {{ __('mksine::frontend.home_placeholder_hero_label') }}
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                @foreach ([
                    'home_placeholder_posts_zone',
                    'home_placeholder_pages_zone',
                    'home_placeholder_media_zone',
                ] as $zoneKey)
                    <div
                        class="home-placeholder-zone rounded-2xl border border-dashed border-slate-200 bg-white/80 p-6 dark:border-slate-700 dark:bg-slate-900/40"
                        aria-label="{{ __('mksine::frontend.'.$zoneKey) }}"
                    >
                        <div class="mb-4 h-10 w-10 rounded-xl bg-violet-100 dark:bg-violet-500/20"></div>
                        <div class="mb-3 h-4 w-24 rounded-lg bg-slate-100 dark:bg-slate-800"></div>
                        <div class="space-y-2">
                            <div class="h-3 w-full rounded-lg bg-slate-100 dark:bg-slate-800"></div>
                            <div class="h-3 w-5/6 rounded-lg bg-slate-100 dark:bg-slate-800"></div>
                            <div class="h-3 w-2/3 rounded-lg bg-slate-100 dark:bg-slate-800"></div>
                        </div>
                        <p class="mt-5 text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">
                            {{ __('mksine::frontend.'.$zoneKey) }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
