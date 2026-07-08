@php
    $primaryCategory = $post->categories->first();
    $categoryPath = $primaryCategory ? $primaryCategory->getBreadcrumbPath() : collect();
    $shareUrl = route('posts.show', $post->slug);
    $shareUrlEncoded = rawurlencode($shareUrl);
    $titleEncoded = rawurlencode($post->title);
    $whatsappTextEncoded = rawurlencode($post->title."\n".$shareUrl);
@endphp

<div class="single-post relative overflow-x-hidden bg-stone-50 dark:bg-slate-950">
    <div class="pointer-events-none absolute inset-x-0 top-0 z-0 h-80 max-h-[50vh] bg-gradient-to-b from-violet-200/20 to-transparent dark:from-violet-950/25" aria-hidden="true"></div>

    @themeDoAction('single.before_breadcrumb')

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
                @foreach ($categoryPath as $cat)
                    <li class="flex items-center gap-x-2" aria-hidden="true">
                        <span class="text-stone-300 dark:text-stone-600">/</span>
                        <a href="{{ $cat->getUrl() }}" class="font-medium text-violet-600 transition hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300">
                            {{ $cat->name }}
                        </a>
                    </li>
                @endforeach
                @if ($categoryPath->isEmpty())
                    <li class="flex items-center gap-x-2">
                        <span class="text-stone-300 dark:text-stone-600" aria-hidden="true">/</span>
                        <a href="{{ route('categories.index') }}" class="font-medium text-violet-600 transition hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300">
                            {{ __('mksine::frontend.categories') }}
                        </a>
                    </li>
                @endif
                <li class="flex min-w-0 items-center gap-x-2">
                    <span class="text-stone-300 dark:text-stone-600" aria-hidden="true">/</span>
                    <span class="truncate font-semibold text-stone-900 dark:text-stone-100">{{ $post->title }}</span>
                </li>
            </ol>
        </div>
    </nav>

    @themeDoAction('single.after_breadcrumb')
    @themeDoAction('single.before_content')

    <div class="relative z-10 mx-auto max-w-[1400px] px-4 py-10 sm:px-6 sm:py-12 lg:px-10 lg:py-14 xl:px-14">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-12 lg:gap-12">
            {{-- Article --}}
            <article class="lg:col-span-8">
                <header class="mb-8 text-center lg:text-start">
                    @if ($primaryCategory)
                        <p class="mb-3">
                            <a
                                href="{{ $primaryCategory->getUrl() }}"
                                class="inline-flex items-center rounded-full border border-violet-200/80 bg-white/90 px-3 py-1 text-xs font-semibold tracking-wide text-violet-700 uppercase shadow-sm dark:border-violet-800/60 dark:bg-slate-900/80 dark:text-violet-300"
                            >
                                {{ $primaryCategory->name }}
                            </a>
                        </p>
                    @endif
                    <h1 class="text-balance text-3xl font-bold tracking-tight text-stone-900 sm:text-4xl lg:text-[2.75rem] lg:leading-[1.15] dark:text-stone-50">
                        {{ $post->title }}
                    </h1>
                    @if ($post->excerpt)
                        <p class="mt-4 max-w-3xl text-pretty text-lg text-stone-600 dark:text-stone-400">
                            {{ $post->excerpt }}
                        </p>
                    @endif

                    <div class="mt-6 flex flex-wrap items-center justify-center gap-4 border-y border-stone-200/80 py-4 text-sm dark:border-slate-800 lg:justify-start">
                        <div class="flex min-w-0 items-center gap-3">
                            @if ($post->author && method_exists($post->author, 'avatar_url') && $post->author->avatar_url)
                                <img
                                    src="{{ $post->author->avatar_url }}"
                                    alt=""
                                    class="h-11 w-11 shrink-0 rounded-2xl object-cover ring-2 ring-white dark:ring-slate-800"
                                    loading="lazy"
                                />
                            @elseif ($post->author)
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-600 text-sm font-bold text-white"
                                    aria-hidden="true"
                                >
                                    {{ $post->author->initials() }}
                                </div>
                            @endif
                            <div class="min-w-0 text-start">
                                <p class="font-semibold text-stone-900 dark:text-stone-100">
                                    @if ($post->author)
                                        <a href="{{ route('authors.show', $post->author->id) }}" class="hover:text-violet-600 dark:hover:text-violet-400">
                                            {{ $post->author->name }}
                                        </a>
                                    @endif
                                </p>
                                <p class="text-xs text-stone-500 dark:text-stone-400">
                                    {{ __('mksine::frontend.author') }}
                                    @if (filled(optional($post->author)->role))
                                        · {{ $post->author->role }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <span class="hidden h-8 w-px bg-stone-200 dark:bg-slate-700 sm:block" aria-hidden="true"></span>
                        <div class="text-center sm:text-start">
                            <p class="font-semibold text-stone-900 dark:text-stone-100">
                                {{ $post->published_at?->isoFormat('LL') ?? '—' }}
                            </p>
                            <p class="text-xs text-stone-500 dark:text-stone-400">{{ __('mksine::frontend.published_date') }}</p>
                        </div>
                    </div>
                </header>

                @if ($post->featuredImage?->url)
                    <figure class="mb-10 overflow-hidden rounded-2xl border border-stone-200/80 bg-stone-100 shadow-none dark:border-slate-700 dark:bg-slate-900">
                        <img
                            src="{{ asset($post->featuredImage->url) }}"
                            alt="{{ $post->title }}"
                            class="aspect-[21/9] w-full object-cover sm:aspect-[2/1]"
                            loading="eager"
                            decoding="async"
                        />
                    </figure>
                @endif

                <div class="single-post-body text-lg">
                    {!! mks_render_content($post->content) !!}
                </div>

                {{-- Share --}}
                <section class="mt-10 rounded-2xl border border-stone-200/90 bg-white/90 p-5 dark:border-slate-700 dark:bg-slate-900/80 sm:p-6" aria-labelledby="single-share-heading">
                    <h2 id="single-share-heading" class="mb-4 text-sm font-bold text-stone-900 dark:text-stone-100">
                        {{ __('mksine::frontend.share_this_article') }}
                    </h2>
                    <div class="flex flex-wrap gap-2">
                        <a
                            href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrlEncoded }}"
                            target="_blank"
                            rel="nofollow noopener noreferrer"
                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-stone-200 bg-stone-50 transition hover:border-violet-200 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800 dark:hover:border-violet-800 dark:hover:bg-violet-950/40"
                            aria-label="{{ __('mksine::frontend.share_on_facebook') }}"
                        >
                            <img src="{{ theme_asset('images/footer-social-facebook.svg') }}" alt="" width="22" height="22" loading="lazy" decoding="async" class="pointer-events-none" />
                        </a>
                        <a
                            href="https://twitter.com/intent/tweet?url={{ $shareUrlEncoded }}&text={{ $titleEncoded }}"
                            target="_blank"
                            rel="nofollow noopener noreferrer"
                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-stone-200 bg-stone-50 transition hover:border-violet-200 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800 dark:hover:border-violet-800 dark:hover:bg-violet-950/40"
                            aria-label="{{ __('mksine::frontend.share_on_x') }}"
                        >
                            <img src="{{ theme_asset('images/footer-social-x.svg') }}" alt="" width="22" height="22" loading="lazy" decoding="async" class="pointer-events-none" />
                        </a>
                        <a
                            href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrlEncoded }}"
                            target="_blank"
                            rel="nofollow noopener noreferrer"
                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-stone-200 bg-stone-50 transition hover:border-violet-200 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800 dark:hover:border-violet-800 dark:hover:bg-violet-950/40"
                            aria-label="{{ __('mksine::frontend.share_on_linkedin') }}"
                        >
                            <img src="{{ theme_asset('images/footer-social-linkedin.svg') }}" alt="" width="22" height="22" loading="lazy" decoding="async" class="pointer-events-none" />
                        </a>
                        <a
                            href="https://t.me/share/url?url={{ $shareUrlEncoded }}&text={{ $titleEncoded }}"
                            target="_blank"
                            rel="nofollow noopener noreferrer"
                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-stone-200 bg-stone-50 transition hover:border-violet-200 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800 dark:hover:border-violet-800 dark:hover:bg-violet-950/40"
                            aria-label="{{ __('mksine::frontend.share_on_telegram') }}"
                        >
                            <img src="{{ theme_asset('images/footer-social-telegram.svg') }}" alt="" width="22" height="22" loading="lazy" decoding="async" class="pointer-events-none" />
                        </a>
                        <a
                            href="https://api.whatsapp.com/send?text={{ $whatsappTextEncoded }}"
                            target="_blank"
                            rel="nofollow noopener noreferrer"
                            class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-stone-200 bg-stone-50 transition hover:border-violet-200 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800 dark:hover:border-violet-800 dark:hover:bg-violet-950/40"
                            aria-label="{{ __('mksine::frontend.share_on_whatsapp') }}"
                        >
                            <img src="{{ theme_asset('images/footer-social-whatsapp.svg') }}" alt="" width="22" height="22" loading="lazy" decoding="async" class="pointer-events-none" />
                        </a>
                        <button
                            type="button"
                            class="inline-flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-xl border border-stone-200 bg-stone-50 transition hover:border-violet-200 hover:bg-violet-50 dark:border-slate-600 dark:bg-slate-800 dark:hover:border-violet-800 dark:hover:bg-violet-950/40"
                            data-copy-url="{{ $shareUrl }}"
                            aria-label="{{ __('mksine::frontend.copy_link') }}"
                        >
                            <img src="{{ theme_asset('images/footer-social-copy-link.svg') }}" alt="" width="22" height="22" loading="lazy" decoding="async" class="pointer-events-none" />
                        </button>
                    </div>
                    <p class="mt-2 hidden text-xs font-medium text-emerald-600 dark:text-emerald-400" data-copy-feedback role="status" aria-live="polite"></p>
                </section>

                {{-- Author --}}
                @if ($post->author)
                    <section class="mt-10 rounded-2xl border border-stone-200/90 bg-white p-6 dark:border-slate-700 dark:bg-slate-900/80 sm:p-8" aria-labelledby="single-author-heading">
                        <h2 id="single-author-heading" class="mb-4 text-lg font-bold text-stone-900 dark:text-stone-100">
                            {{ __('mksine::frontend.about_the_author') }}
                        </h2>
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                            @if (method_exists($post->author, 'avatar_url') && $post->author->avatar_url)
                                <img src="{{ $post->author->avatar_url }}" alt="" class="h-20 w-20 shrink-0 rounded-2xl object-cover ring-2 ring-violet-100 dark:ring-violet-900/50" loading="lazy" />
                            @else
                                <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-600 text-2xl font-bold text-white" aria-hidden="true">
                                    {{ $post->author->initials() }}
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base font-bold text-stone-900 dark:text-stone-100">{{ $post->author->name }}</h3>
                                @if (filled($post->author->bio))
                                    <p class="mt-2 text-sm leading-relaxed text-stone-600 dark:text-stone-400">{{ $post->author->bio }}</p>
                                @endif
                                <a href="{{ route('authors.show', $post->author->id) }}" class="mt-3 inline-flex text-sm font-semibold text-violet-600 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300">
                                    {{ __('mksine::frontend.view_all_articles_by_author') }}
                                </a>
                            </div>
                        </div>
                    </section>
                @endif

                @livewire('mksine::frontend.post-comments', ['postId' => $post->id])
            </article>

            @themeDoAction('single.after_article')

            {{-- Sidebar --}}
            <aside class="lg:col-span-4">
                <div class="space-y-6 lg:sticky lg:top-24">
                    <div class="rounded-2xl border border-stone-200/90 bg-white p-5 dark:border-slate-700 dark:bg-slate-900/80 sm:p-6">
                        <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-stone-900 dark:text-stone-100">
                            {{ __('mksine::frontend.single_recent_posts') }}
                        </h2>
                        @if ($recentPosts->isEmpty())
                            <p class="text-sm text-stone-500 dark:text-stone-400">{{ __('mksine::frontend.no_articles_yet') }}</p>
                        @else
                            <ul class="space-y-4">
                                @foreach ($recentPosts as $recent)
                                    <li class="group flex gap-3 border-b border-stone-100 pb-4 last:border-0 last:pb-0 dark:border-slate-800">
                                        <a href="{{ route('posts.show', $recent->slug) }}" class="relative h-16 w-20 shrink-0 overflow-hidden rounded-xl bg-stone-100 dark:bg-slate-800">
                                            @if ($recent->featuredImage?->url)
                                                <img src="{{ asset($recent->featuredImage->url) }}" alt="" class="h-full w-full object-cover transition group-hover:scale-105" loading="lazy" />
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-stone-400 dark:text-stone-500">
                                                    <x-heroicon-o-photo class="h-6 w-6" />
                                                </span>
                                            @endif
                                        </a>
                                        <div class="min-w-0 flex-1">
                                            <a href="{{ route('posts.show', $recent->slug) }}" class="line-clamp-2 text-sm font-semibold text-stone-900 transition group-hover:text-violet-600 dark:text-stone-100 dark:group-hover:text-violet-400">
                                                {{ $recent->title }}
                                            </a>
                                            <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">{{ $recent->published_at?->isoFormat('LL') }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <a href="{{ route('posts.index') }}" class="mt-4 inline-flex text-sm font-semibold text-violet-600 hover:text-violet-700 dark:text-violet-400">
                            {{ __('mksine::frontend.all_posts') }}
                        </a>
                    </div>

                    <div class="rounded-2xl border border-stone-200/90 bg-white p-5 dark:border-slate-700 dark:bg-slate-900/80 sm:p-6">
                        <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-stone-900 dark:text-stone-100">
                            {{ __('mksine::frontend.single_browse_categories') }}
                        </h2>
                        @if ($sidebarCategories->isEmpty())
                            <p class="text-sm text-stone-500 dark:text-stone-400">{{ __('mksine::frontend.no_categories_yet') }}</p>
                        @else
                            <ul class="space-y-2">
                                @foreach ($sidebarCategories as $cat)
                                    <li class="flex items-center justify-between gap-2 text-sm">
                                        <a href="{{ $cat->getUrl() }}" class="font-medium text-stone-800 transition hover:text-violet-600 dark:text-stone-200 dark:hover:text-violet-400">
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

        @themeDoAction('single.after_content')
        @themeDoAction('single.before_related')

        @if ($relatedPosts->isNotEmpty())
            <section class="mt-16 border-t border-stone-200/80 pt-14 dark:border-slate-800" aria-labelledby="single-related-heading">
                <h2 id="single-related-heading" class="mb-8 text-2xl font-bold tracking-tight text-stone-900 dark:text-stone-50 sm:text-3xl">
                    {{ __('mksine::frontend.related_articles') }}
                </h2>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($relatedPosts as $related)
                        <article class="group flex h-full flex-col overflow-hidden rounded-2xl border border-stone-200/90 bg-white transition hover:border-violet-200/80 dark:border-slate-700 dark:bg-slate-900/80 dark:hover:border-violet-800/60">
                            <a href="{{ route('posts.show', $related->slug) }}" class="block shrink-0">
                                <div class="relative aspect-[16/10] overflow-hidden bg-stone-100 dark:bg-slate-800">
                                    @if ($related->featuredImage?->url)
                                        <img src="{{ asset($related->featuredImage->url) }}" alt="" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]" loading="lazy" />
                                    @else
                                        <div class="flex h-full w-full items-center justify-center">
                                            <x-heroicon-o-photo class="h-14 w-14 text-stone-300 dark:text-stone-600" />
                                        </div>
                                    @endif
                                </div>
                            </a>
                            <div class="flex flex-1 flex-col p-5">
                                <h3 class="text-lg font-bold leading-snug text-stone-900 dark:text-stone-100">
                                    <a href="{{ route('posts.show', $related->slug) }}" class="hover:text-violet-600 dark:hover:text-violet-400">{{ $related->title }}</a>
                                </h3>
                                @if ($related->excerpt)
                                    <p class="mt-2 line-clamp-2 flex-1 text-sm text-stone-600 dark:text-stone-400">{{ $related->excerpt }}</p>
                                @endif
                                <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-stone-100 pt-4 text-xs text-stone-500 dark:border-slate-800 dark:text-stone-400">
                                    @if ($related->author)
                                        <a href="{{ route('authors.show', $related->author->id) }}" class="font-medium hover:text-violet-600 dark:hover:text-violet-400" onclick="event.stopPropagation();">{{ $related->author->name }}</a>
                                    @endif
                                    <time datetime="{{ $related->published_at?->toIso8601String() }}">{{ $related->published_at?->isoFormat('LL') }}</time>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
        @themeDoAction('single.after_related')
    </div>
</div>

<script>
    document.querySelectorAll('[data-copy-url]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = btn.getAttribute('data-copy-url') || '';
            var feedback = btn.closest('section') && btn.closest('section').querySelector('[data-copy-feedback]');
            var show = function (ok) {
                if (!feedback) return;
                feedback.textContent = ok ? @json(__('mksine::frontend.link_copied')) : '';
                feedback.classList.toggle('hidden', !ok);
                if (ok) setTimeout(function () { feedback.classList.add('hidden'); }, 2500);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function () { show(true); }).catch(function () { show(false); prompt('', url); });
            } else {
                prompt('', url);
            }
        });
    });
</script>
