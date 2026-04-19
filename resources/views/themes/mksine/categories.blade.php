@php
    $rootCount = $categories->count();
    $totalArticles = $categories->sum('posts_count');
@endphp

<div class="categories-index relative overflow-x-hidden bg-stone-50 dark:bg-slate-950">
    <div class="pointer-events-none absolute inset-x-0 top-0 z-0 h-80 max-h-[50vh] bg-gradient-to-b from-violet-200/20 to-transparent dark:from-violet-950/25" aria-hidden="true"></div>

    @themeDoAction('categories.before_breadcrumb')

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
                    <span class="font-semibold text-stone-900 dark:text-stone-100">{{ __('mksine::frontend.all_categories') }}</span>
                </li>
            </ol>
        </div>
    </nav>

    @themeDoAction('categories.after_breadcrumb')
    @themeDoAction('categories.before_header')

    <div class="relative z-10 mx-auto max-w-[1400px] px-4 py-10 sm:px-6 sm:py-12 lg:px-10 lg:py-14 xl:px-14">
        <header class="mb-10 lg:mb-12">
            <h1 class="text-balance text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl lg:text-[2.5rem] lg:leading-tight dark:text-stone-50">
                {{ __('mksine::frontend.all_categories') }}
            </h1>
            <p class="mt-4 max-w-2xl text-pretty text-lg text-stone-600 dark:text-stone-400">
                {{ __('mksine::frontend.browse_by_category') }}
            </p>
            @if ($rootCount > 0)
                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center rounded-full border border-violet-200/80 bg-white/90 px-4 py-1.5 text-sm font-semibold text-violet-800 shadow-sm dark:border-violet-800/50 dark:bg-slate-900/80 dark:text-violet-200">
                        {{ number_format($rootCount) }} {{ __('mksine::frontend.categories') }}
                    </span>
                    <span class="inline-flex items-center rounded-full border border-stone-200/90 bg-stone-50/80 px-4 py-1.5 text-sm font-medium text-stone-700 dark:border-slate-600 dark:bg-slate-800/60 dark:text-stone-300">
                        {{ number_format($totalArticles) }} {{ __('mksine::frontend.articles') }}
                    </span>
                    <a
                        href="{{ route('posts.index') }}"
                        class="inline-flex items-center text-sm font-semibold text-violet-600 transition hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300"
                    >
                        {{ __('mksine::frontend.all_posts') }}
                        <span class="ms-1" aria-hidden="true">→</span>
                    </a>
                </div>
            @endif
        </header>

        @themeDoAction('categories.after_header')
        @themeDoAction('categories.before_content')

        <div class="space-y-8 lg:space-y-10">
            @forelse ($categories as $category)
                <section class="rounded-2xl border border-stone-200/90 bg-white/95 p-6 shadow-none dark:border-slate-700 dark:bg-slate-900/80 sm:p-8">
                    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <h2 class="text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100 sm:text-2xl">
                                <a href="{{ $category->getUrl() }}" class="transition hover:text-violet-600 dark:hover:text-violet-400">
                                    {{ $category->name }}
                                </a>
                            </h2>
                            @if ($category->posts_count > 0)
                                <p class="mt-1 text-sm font-medium text-stone-500 dark:text-stone-400">
                                    {{ number_format($category->posts_count) }} {{ __('mksine::frontend.articles') }}
                                </p>
                            @endif
                        </div>
                        <a
                            href="{{ $category->getUrl() }}"
                            class="inline-flex shrink-0 items-center justify-center rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-violet-500 hover:to-indigo-500 dark:shadow-violet-900/30"
                        >
                            {{ __('mksine::frontend.view_articles') }}
                        </a>
                    </div>

                    @if (filled($category->description))
                        <p class="mb-6 max-w-3xl text-sm leading-relaxed text-stone-600 dark:text-stone-400 sm:text-base">
                            {{ $category->description }}
                        </p>
                    @endif

                    @if ($category->children->isNotEmpty())
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($category->children as $child)
                                <a
                                    href="{{ $child->getUrl() }}"
                                    class="group flex flex-col rounded-2xl border border-stone-200/90 bg-stone-50/50 p-5 transition hover:border-violet-200/90 hover:bg-violet-50/40 dark:border-slate-700 dark:bg-slate-800/40 dark:hover:border-violet-800/60 dark:hover:bg-violet-950/20"
                                >
                                    <h3 class="font-semibold text-stone-900 transition group-hover:text-violet-600 dark:text-stone-100 dark:group-hover:text-violet-400">
                                        {{ $child->name }}
                                    </h3>
                                    @if (isset($child->posts_count) && $child->posts_count > 0)
                                        <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">
                                            {{ number_format($child->posts_count) }} {{ __('mksine::frontend.articles') }}
                                        </p>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>
            @empty
                <div class="rounded-2xl border border-dashed border-stone-200 bg-white/80 px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900/50">
                    <p class="text-stone-600 dark:text-stone-400">{{ __('mksine::frontend.no_categories_yet') }}</p>
                    <a href="{{ route('home') }}" class="mt-4 inline-flex text-sm font-semibold text-violet-600 hover:text-violet-700 dark:text-violet-400">
                        {{ __('mksine::frontend.home') }}
                    </a>
                </div>
            @endforelse
        </div>
    </div>

    @themeDoAction('categories.after_content')
</div>
