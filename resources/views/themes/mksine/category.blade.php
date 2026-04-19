<div class="category-show relative overflow-x-hidden bg-stone-50 dark:bg-slate-950">
    <div class="pointer-events-none absolute inset-x-0 top-0 z-0 h-80 max-h-[50vh] bg-gradient-to-b from-violet-200/20 to-transparent dark:from-violet-950/25" aria-hidden="true"></div>

    @themeDoAction('category.before_breadcrumb')

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
                <li class="flex items-center gap-x-2">
                    <span class="text-stone-300 dark:text-stone-600" aria-hidden="true">/</span>
                    <a href="{{ route('categories.index') }}" class="font-medium text-violet-600 transition hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300">
                        {{ __('mksine::frontend.categories') }}
                    </a>
                </li>
                @foreach ($breadcrumbPath as $crumb)
                    <li class="flex min-w-0 items-center gap-x-2">
                        <span class="text-stone-300 dark:text-stone-600" aria-hidden="true">/</span>
                        @if ($crumb->is($category))
                            <span class="truncate font-semibold text-stone-900 dark:text-stone-100">{{ $crumb->name }}</span>
                        @else
                            <a href="{{ $crumb->getUrl() }}" class="truncate font-medium text-violet-600 transition hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300">
                                {{ $crumb->name }}
                            </a>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    </nav>

    @themeDoAction('category.after_breadcrumb')
    @themeDoAction('category.before_header')

    <div class="relative z-10 mx-auto max-w-[1400px] px-4 py-10 sm:px-6 sm:py-12 lg:px-10 lg:py-14 xl:px-14">
        <header class="mb-10 lg:mb-12">
            <h1 class="text-balance text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl lg:text-[2.5rem] lg:leading-tight dark:text-stone-50">
                {{ $category->name }}
            </h1>
            @if (filled($category->description))
                <p class="mt-4 max-w-3xl text-pretty text-lg text-stone-600 dark:text-stone-400">
                    {{ $category->description }}
                </p>
            @endif
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center rounded-full border border-violet-200/80 bg-white/90 px-4 py-1.5 text-sm font-semibold text-violet-800 shadow-sm dark:border-violet-800/50 dark:bg-slate-900/80 dark:text-violet-200">
                    {{ number_format($posts->total()) }} {{ __('mksine::frontend.articles') }}
                </span>
                <a
                    href="{{ route('categories.index') }}"
                    class="inline-flex items-center text-sm font-semibold text-violet-600 transition hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300"
                >
                    {{ __('mksine::frontend.all_categories') }}
                    <span class="ms-1" aria-hidden="true">→</span>
                </a>
            </div>
        </header>

        @themeDoAction('category.after_header')
        @themeDoAction('category.before_content')

        <div class="grid grid-cols-1 gap-10 lg:grid-cols-12 lg:gap-12">
            <div class="lg:col-span-8">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    @forelse ($posts as $post)
                        @php
                            $cardCategory = $post->categories->first();
                        @endphp
                        <article class="group flex h-full flex-col overflow-hidden rounded-2xl border border-stone-200/90 bg-white transition hover:border-violet-200/80 dark:border-slate-700 dark:bg-slate-900/80 dark:hover:border-violet-800/60">
                            <a href="{{ route('posts.show', $post->slug) }}" class="block shrink-0">
                                <div class="relative aspect-[16/10] overflow-hidden bg-stone-100 dark:bg-slate-800">
                                    @if ($post->featuredImage?->url)
                                        <img
                                            src="{{ asset($post->featuredImage->url) }}"
                                            alt=""
                                            class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                                            loading="lazy"
                                            decoding="async"
                                        />
                                    @else
                                        <div class="flex h-full w-full items-center justify-center">
                                            <x-heroicon-o-photo class="h-14 w-14 text-stone-300 dark:text-stone-600" />
                                        </div>
                                    @endif
                                </div>
                            </a>
                            <div class="flex flex-1 flex-col p-5">
                                @if ($cardCategory)
                                    <p class="mb-2">
                                        <a
                                            href="{{ $cardCategory->getUrl() }}"
                                            class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-semibold tracking-wide uppercase transition {{ $cardCategory->is($category) ? 'border-violet-400 bg-violet-100 text-violet-900 dark:border-violet-600 dark:bg-violet-950/60 dark:text-violet-200' : 'border-violet-200/80 bg-violet-50/80 text-violet-700 hover:border-violet-300 hover:bg-violet-100/80 dark:border-violet-800/60 dark:bg-violet-950/40 dark:text-violet-300 dark:hover:border-violet-700' }}"
                                        >
                                            {{ $cardCategory->name }}
                                        </a>
                                    </p>
                                @endif
                                <h2 class="text-lg font-bold leading-snug text-stone-900 dark:text-stone-100">
                                    <a href="{{ route('posts.show', $post->slug) }}" class="transition hover:text-violet-600 dark:hover:text-violet-400">
                                        {{ $post->title }}
                                    </a>
                                </h2>
                                @if (filled($post->excerpt))
                                    <p class="mt-2 line-clamp-2 flex-1 text-sm text-stone-600 dark:text-stone-400">{{ $post->excerpt }}</p>
                                @endif
                                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-stone-100 pt-4 text-xs text-stone-500 dark:border-slate-800 dark:text-stone-400">
                                    @if ($post->author)
                                        <a href="{{ route('authors.show', $post->author->id) }}" class="font-medium text-stone-700 transition hover:text-violet-600 dark:text-stone-300 dark:hover:text-violet-400">
                                            {{ $post->author->name }}
                                        </a>
                                    @else
                                        <span class="font-medium text-stone-500 dark:text-stone-500">—</span>
                                    @endif
                                    <time datetime="{{ $post->published_at?->toIso8601String() }}">{{ $post->published_at?->isoFormat('LL') ?? '—' }}</time>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-stone-200 bg-white/80 px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900/50">
                            <p class="text-stone-600 dark:text-stone-400">{{ __('mksine::frontend.no_articles_in_category') }}</p>
                            <a href="{{ route('categories.index') }}" class="mt-4 inline-flex text-sm font-semibold text-violet-600 hover:text-violet-700 dark:text-violet-400">
                                {{ __('mksine::frontend.all_categories') }}
                            </a>
                        </div>
                    @endforelse
                </div>

                @if ($posts->hasPages())
                    <div class="mt-12 flex justify-center">
                        {{ $posts->onEachSide(1)->links('mksine::components.pagination') }}
                    </div>
                @endif
            </div>

            <aside class="lg:col-span-4">
                <div class="space-y-6 lg:sticky lg:top-24">
                    <div class="rounded-2xl border border-stone-200/90 bg-white p-5 dark:border-slate-700 dark:bg-slate-900/80 sm:p-6">
                        <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-stone-900 dark:text-stone-100">
                            {{ __('mksine::frontend.search') }}
                        </h2>
                        <form action="{{ route('posts.index') }}" method="GET" class="flex flex-col gap-2 sm:flex-row">
                            <label class="sr-only" for="category-sidebar-search">{{ __('mksine::frontend.search_placeholder') }}</label>
                            <input
                                id="category-sidebar-search"
                                type="search"
                                name="search"
                                placeholder="{{ __('mksine::frontend.search_placeholder') }}"
                                class="min-w-0 flex-1 rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-sm text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-stone-100 dark:placeholder:text-stone-500 dark:focus:border-violet-500 sm:rounded-e-none sm:rounded-s-xl"
                            />
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-violet-500 hover:to-indigo-500 sm:rounded-s-none sm:rounded-e-xl"
                            >
                                <span class="sr-only">{{ __('mksine::frontend.search') }}</span>
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </button>
                        </form>
                    </div>

                    <div class="rounded-2xl border border-stone-200/90 bg-white p-5 dark:border-slate-700 dark:bg-slate-900/80 sm:p-6">
                        <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-stone-900 dark:text-stone-100">
                            {{ __('mksine::frontend.categories') }}
                        </h2>
                        @if ($categories->isEmpty())
                            <p class="text-sm text-stone-500 dark:text-stone-400">{{ __('mksine::frontend.no_categories_yet') }}</p>
                        @else
                            <ul class="space-y-2">
                                @foreach ($categories as $cat)
                                    <li class="flex items-center justify-between gap-2 text-sm">
                                        <a
                                            href="{{ $cat->getUrl() }}"
                                            class="font-medium transition {{ $cat->id === $category->id ? 'text-violet-700 dark:text-violet-300' : 'text-stone-800 hover:text-violet-600 dark:text-stone-200 dark:hover:text-violet-400' }}"
                                        >
                                            {{ $cat->name }}
                                        </a>
                                        <span class="shrink-0 rounded-full bg-stone-100 px-2 py-0.5 text-xs tabular-nums text-stone-600 dark:bg-slate-800 dark:text-stone-400">{{ $cat->posts_count }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <a href="{{ route('categories.index') }}" class="mt-4 inline-flex text-sm font-semibold text-violet-600 hover:text-violet-700 dark:text-violet-400">
                            {{ __('mksine::frontend.all_categories') }}
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    @themeDoAction('category.after_content')
</div>
