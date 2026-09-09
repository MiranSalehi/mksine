@php
    $tagCount = $tags->count();
    $totalPosts = $tags->sum('posts_count');
    $totalPages = $tags->sum('pages_count');
@endphp

<div class="tags-index relative overflow-x-hidden bg-stone-50 dark:bg-slate-950">
    <div class="pointer-events-none absolute inset-x-0 top-0 z-0 h-80 max-h-[50vh] bg-gradient-to-b from-violet-200/20 to-transparent dark:from-violet-950/25" aria-hidden="true"></div>

    @themeDoAction('tags.before_breadcrumb')

    <nav
        aria-label="{{ __('mksine::frontend.breadcrumb') }}"
        class="relative z-10 border-b border-stone-200/90 bg-white/80 backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/80"
    >
        <div class="mx-auto max-w-[1400px] px-4 py-3 sm:px-6 lg:px-10 xl:px-14">
            <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-stone-600 dark:text-stone-400">
                <li>
                    <a href="{{ route('home') }}" class="font-medium text-violet-600 transition hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300">
                        {{ __('mksine::frontend.home') }}
                    </a>
                </li>
                <li class="flex min-w-0 items-center gap-x-2">
                    <span class="text-stone-300 dark:text-stone-600" aria-hidden="true">/</span>
                    <span class="font-semibold text-stone-900 dark:text-stone-100">{{ __('mksine::frontend.all_tags') }}</span>
                </li>
            </ol>
        </div>
    </nav>

    @themeDoAction('tags.after_breadcrumb')
    @themeDoAction('tags.before_header')

    <div class="relative z-10 mx-auto max-w-[1400px] px-4 py-10 sm:px-6 sm:py-12 lg:px-10 lg:py-14 xl:px-14">
        <header class="mb-10 lg:mb-12">
            <h1 class="text-balance text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl lg:text-[2.5rem] lg:leading-tight dark:text-stone-50">
                {{ __('mksine::frontend.all_tags') }}
            </h1>
            <p class="mt-4 max-w-2xl text-pretty text-lg text-stone-600 dark:text-stone-400">
                {{ __('mksine::frontend.browse_by_tag') }}
            </p>
            @if ($tagCount > 0)
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center rounded-full border border-violet-200/80 bg-white/90 px-4 py-1.5 text-sm font-semibold text-violet-800 shadow-sm dark:border-violet-800/50 dark:bg-slate-900/80 dark:text-violet-200">
                        {{ number_format($tagCount) }} {{ __('mksine::frontend.tags') }}
                    </span>
                    <span class="inline-flex items-center rounded-full border border-stone-200/90 bg-stone-50/80 px-4 py-1.5 text-sm font-medium text-stone-700 dark:border-slate-600 dark:bg-slate-800/60 dark:text-stone-300">
                        {{ number_format($totalPosts) }} {{ __('mksine::frontend.articles') }}
                    </span>
                    <span class="inline-flex items-center rounded-full border border-stone-200/90 bg-stone-50/80 px-4 py-1.5 text-sm font-medium text-stone-700 dark:border-slate-600 dark:bg-slate-800/60 dark:text-stone-300">
                        {{ number_format($totalPages) }} {{ __('mksine::frontend.pages') }}
                    </span>
                </div>
            @endif
        </header>

        @themeDoAction('tags.after_header')
        @themeDoAction('tags.before_content')

        <div class="flex flex-wrap gap-3">
            @forelse ($tags as $tag)
                <a
                    href="{{ $tag->getUrl() }}"
                    class="inline-flex items-center gap-2 rounded-full border border-stone-200/90 bg-white px-4 py-2 text-sm font-medium text-stone-800 transition hover:border-violet-200 hover:text-violet-700 dark:border-slate-700 dark:bg-slate-900/80 dark:text-stone-100 dark:hover:border-violet-800 dark:hover:text-violet-300"
                >
                    <span>{{ $tag->name }}</span>
                    @if (($tag->posts_count + $tag->pages_count) > 0)
                        <span class="text-stone-400 dark:text-stone-500">{{ number_format($tag->posts_count + $tag->pages_count) }}</span>
                    @endif
                </a>
            @empty
                <div class="w-full rounded-2xl border border-dashed border-stone-200 bg-white/80 px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900/50">
                    <p class="text-stone-600 dark:text-stone-400">{{ __('mksine::frontend.no_tags_yet') }}</p>
                    <a href="{{ route('home') }}" class="mt-4 inline-flex text-sm font-semibold text-violet-600 hover:text-violet-700 dark:text-violet-400">
                        {{ __('mksine::frontend.home') }}
                    </a>
                </div>
            @endforelse
        </div>
    </div>

    @themeDoAction('tags.after_content')
</div>
