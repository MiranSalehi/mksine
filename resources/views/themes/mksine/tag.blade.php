<div class="tag-show relative overflow-x-hidden bg-stone-50 dark:bg-slate-950">
    <div class="pointer-events-none absolute inset-x-0 top-0 z-0 h-80 max-h-[50vh] bg-gradient-to-b from-violet-200/20 to-transparent dark:from-violet-950/25" aria-hidden="true"></div>

    @themeDoAction('tag.before_breadcrumb')

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
                    <a href="{{ route('tags.index') }}" class="font-medium text-violet-600 transition hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300">
                        {{ __('mksine::frontend.tags') }}
                    </a>
                </li>
                <li class="flex min-w-0 items-center gap-x-2">
                    <span class="text-stone-300 dark:text-stone-600" aria-hidden="true">/</span>
                    <span class="truncate font-semibold text-stone-900 dark:text-stone-100">{{ $tag->name }}</span>
                </li>
            </ol>
        </div>
    </nav>

    @themeDoAction('tag.after_breadcrumb')
    @themeDoAction('tag.before_header')

    <div class="relative z-10 mx-auto max-w-[1400px] px-4 py-10 sm:px-6 sm:py-12 lg:px-10 lg:py-14 xl:px-14">
        <header class="mb-10 lg:mb-12">
            <h1 class="text-balance text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl lg:text-[2.5rem] lg:leading-tight dark:text-stone-50">
                {{ $tag->name }}
            </h1>
            @if (filled($tag->description))
                <div class="mt-4 max-w-3xl text-pretty text-lg text-stone-600 dark:text-stone-400">
                    {!! mks_render_content($tag->description) !!}
                </div>
            @endif
            <div class="mt-6 flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center rounded-full border border-violet-200/80 bg-white/90 px-4 py-1.5 text-sm font-semibold text-violet-800 shadow-sm dark:border-violet-800/50 dark:bg-slate-900/80 dark:text-violet-200">
                    {{ number_format($posts->total()) }} {{ __('mksine::frontend.articles') }}
                </span>
                <span class="inline-flex items-center rounded-full border border-stone-200/90 bg-stone-50/80 px-4 py-1.5 text-sm font-medium text-stone-700 dark:border-slate-600 dark:bg-slate-800/60 dark:text-stone-300">
                    {{ number_format($pages->total()) }} {{ __('mksine::frontend.pages') }}
                </span>
                <a
                    href="{{ route('tags.index') }}"
                    class="inline-flex items-center text-sm font-semibold text-violet-600 transition hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300"
                >
                    {{ __('mksine::frontend.all_tags') }}
                    <span class="ms-1" aria-hidden="true">→</span>
                </a>
            </div>
        </header>

        @themeDoAction('tag.after_header')
        @themeDoAction('tag.before_content')

        <section class="mb-12">
            <h2 class="mb-6 text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100">
                {{ __('mksine::frontend.articles') }}
            </h2>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($posts as $post)
                    <article class="group flex h-full flex-col overflow-hidden rounded-2xl border border-stone-200/90 bg-white transition hover:border-violet-200/80 dark:border-slate-700 dark:bg-slate-900/80 dark:hover:border-violet-800/60">
                        <a href="{{ route('posts.show', $post->slug) }}" class="block shrink-0">
                            <div class="relative aspect-[16/10] overflow-hidden bg-stone-100 dark:bg-slate-800">
                                @if ($post->featuredImage?->url)
                                    <img
                                        src="{{ $post->featuredImage->url }}"
                                        alt="{{ $post->title }}"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.03]"
                                    >
                                @endif
                            </div>
                        </a>
                        <div class="flex flex-1 flex-col p-5">
                            <h3 class="text-lg font-semibold text-stone-900 dark:text-stone-100">
                                <a href="{{ route('posts.show', $post->slug) }}" class="transition hover:text-violet-600 dark:hover:text-violet-400">
                                    {{ $post->title }}
                                </a>
                            </h3>
                        </div>
                    </article>
                @empty
                    <p class="col-span-full text-stone-600 dark:text-stone-400">{{ __('mksine::frontend.no_articles_with_tag') }}</p>
                @endforelse
            </div>
            @if ($posts->hasPages())
                <div class="mt-8">
                    {{ $posts->links() }}
                </div>
            @endif
        </section>

        <section>
            <h2 class="mb-6 text-xl font-bold tracking-tight text-stone-900 dark:text-stone-100">
                {{ __('mksine::frontend.pages') }}
            </h2>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($pages as $page)
                    <article class="rounded-2xl border border-stone-200/90 bg-white p-5 dark:border-slate-700 dark:bg-slate-900/80">
                        <h3 class="text-lg font-semibold text-stone-900 dark:text-stone-100">
                            <a href="{{ route('pages.show', $page->slug) }}" class="transition hover:text-violet-600 dark:hover:text-violet-400">
                                {{ $page->title }}
                            </a>
                        </h3>
                    </article>
                @empty
                    <p class="col-span-full text-stone-600 dark:text-stone-400">{{ __('mksine::frontend.no_pages_with_tag') }}</p>
                @endforelse
            </div>
            @if ($pages->hasPages())
                <div class="mt-8">
                    {{ $pages->links() }}
                </div>
            @endif
        </section>
    </div>

    @themeDoAction('tag.after_content')
</div>
