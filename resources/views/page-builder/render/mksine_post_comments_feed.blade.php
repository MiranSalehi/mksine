@php
    use Illuminate\Support\Str;

    $d = $data ?? [];
    $postIdRaw = $d['post_id'] ?? null;
    $postId = ($postIdRaw !== null && $postIdRaw !== '') ? (int) $postIdRaw : 0;
    $maxRoot = max(1, min(50, (int) ($d['max_root_comments'] ?? 12)));
    $quoteMaxChars = max(80, min(500, (int) ($d['quote_max_chars'] ?? 220)));
    $td = $d['text_direction'] ?? 'auto';
    $sectionDir = match ($td) {
        'rtl' => 'rtl',
        'ltr' => 'ltr',
        default => null,
    };

    $post = null;
    $postMissing = false;
    if ($postId > 0) {
        $post = \Miran\Mksine\Models\Post::query()->where('status', 'published')->find($postId);
        if (! $post) {
            $postMissing = true;
        }
    }

    $commentWith = [
        'commentable',
        'user',
        'replies' => fn ($q) => $q->approved()->with('user')->orderBy('created_at'),
    ];

    $comments = collect();
    if (! $postMissing) {
        if ($post) {
            $comments = \Miran\Mksine\Models\Comment::query()
                ->where('commentable_type', \Miran\Mksine\Models\Post::class)
                ->where('commentable_id', $post->id)
                ->approved()
                ->root()
                ->with($commentWith)
                ->orderBy('created_at')
                ->limit($maxRoot)
                ->get();
        } else {
            $comments = \Miran\Mksine\Models\Comment::query()
                ->approved()
                ->root()
                ->where('commentable_type', \Miran\Mksine\Models\Post::class)
                ->whereHasMorph('commentable', [\Miran\Mksine\Models\Post::class], fn ($q) => $q->where('status', 'published'))
                ->with($commentWith)
                ->orderByDesc('created_at')
                ->limit($maxRoot)
                ->get();
        }
    }

    $palettes = [
        ['glow' => 'from-violet-500 to-purple-600', 'border' => 'border-violet-200/90 dark:border-violet-800/55', 'avatar' => 'from-violet-500 to-purple-600'],
        ['glow' => 'from-indigo-500 to-indigo-600', 'border' => 'border-indigo-200/90 dark:border-indigo-800/55', 'avatar' => 'from-indigo-500 to-indigo-600'],
        ['glow' => 'from-emerald-500 to-teal-600', 'border' => 'border-emerald-200/90 dark:border-emerald-800/55', 'avatar' => 'from-emerald-500 to-teal-600'],
        ['glow' => 'from-amber-500 to-orange-600', 'border' => 'border-amber-200/90 dark:border-amber-800/55', 'avatar' => 'from-amber-500 to-orange-600'],
        ['glow' => 'from-fuchsia-500 to-pink-600', 'border' => 'border-fuchsia-200/90 dark:border-fuchsia-800/55', 'avatar' => 'from-fuchsia-500 to-pink-600'],
        ['glow' => 'from-indigo-500 to-blue-600', 'border' => 'border-indigo-200/90 dark:border-indigo-800/55', 'avatar' => 'from-indigo-500 to-blue-600'],
    ];
@endphp

<section
    class="mksine-post-comments-feed home-testimonials relative overflow-hidden border-t border-stone-200/80 bg-stone-50 py-10 sm:py-12 dark:border-slate-800 dark:bg-slate-950"
    @if ($sectionDir !== null) dir="{{ $sectionDir }}" @endif
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    aria-labelledby="mksine-post-comments-feed-heading"
>
    <div
        class="pointer-events-none absolute inset-x-0 top-0 h-64 bg-gradient-to-b from-violet-200/25 to-transparent dark:from-violet-900/20"
        aria-hidden="true"
    ></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if ($postMissing)
            <div
                class="rounded-2xl border border-amber-200 bg-amber-50/90 p-6 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-100"
                role="status"
            >
                <p class="m-0 text-sm font-medium">{{ __('mksine::page_builder.comments_feed_post_not_found') }}</p>
            </div>
        @else
            <header class="mb-10 flex flex-col gap-8 lg:mb-14 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-2xl text-center lg:text-start">
                    @if (! empty($d['badge']))
                        <div
                            class="mb-5 inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/60 px-4 py-2 text-sm font-medium text-violet-700 shadow-lg backdrop-blur-xl dark:border-slate-600/50 dark:bg-slate-800/80 dark:text-violet-300 dark:shadow-black/25"
                        >
                            {{ $d['badge'] }}
                        </div>
                    @endif
                    <h2
                        id="mksine-post-comments-feed-heading"
                        class="text-3xl leading-tight font-bold text-gray-900 sm:text-4xl lg:text-5xl dark:text-gray-50"
                    >
                        {{ $d['title_prefix'] ?? '' }}<span
                            class="bg-gradient-to-r from-violet-600 to-indigo-600 bg-clip-text text-transparent rtl:bg-gradient-to-l dark:from-violet-400 dark:to-indigo-400"
                        >{{ $d['title_accent'] ?? '' }}</span>
                    </h2>
                    @if (! empty($d['subheading']))
                        <p
                            class="mt-4 max-w-xl text-pretty text-base leading-relaxed text-gray-600 sm:text-lg dark:text-gray-400"
                        >
                            {{ $d['subheading'] }}
                        </p>
                    @endif
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-500">
                        @if ($post)
                            <a
                                href="{{ route('posts.show', $post->slug) }}#comments-section"
                                class="font-medium text-violet-600 underline-offset-2 hover:underline dark:text-violet-400"
                            >{{ $post->title }}</a>
                        @else
                            <a
                                href="{{ route('posts.index') }}"
                                class="font-medium text-violet-600 underline-offset-2 hover:underline dark:text-violet-400"
                            >{{ __('mksine::page_builder.comments_feed_blog_link') }}</a>
                        @endif
                    </p>
                </div>
                @if (! empty($d['aside']))
                    <p
                        class="hidden max-w-xs text-sm leading-relaxed text-gray-500 lg:block dark:text-gray-500"
                    >
                        {{ $d['aside'] }}
                    </p>
                @endif
            </header>

            @if ($comments->isEmpty())
                <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                    {{ __('mksine::page_builder.comments_feed_empty') }}
                </p>
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
                    @foreach ($comments as $comment)
                        @php
                            $palette = $palettes[abs(crc32((string) $comment->id)) % count($palettes)];
                            $user = $comment->user;
                            $avatarUrl = $user && method_exists($user, 'avatar_url') ? $user->avatar_url : null;
                            $displayName = $comment->author_display_name;
                            $initial = Str::upper(Str::substr(trim((string) $displayName), 0, 1));
                            $plainBody = trim(strip_tags((string) $comment->content));
                            $quoteVisible = Str::limit($plainBody, $quoteMaxChars, '…');
                            $replyCount = $comment->replies->count();
                            $targetPost = $post ?? ($comment->commentable instanceof \Miran\Mksine\Models\Post ? $comment->commentable : null);
                        @endphp
                        <article
                            class="group relative flex min-h-[11rem] flex-col overflow-hidden rounded-2xl border bg-white/90 p-5 backdrop-blur-xl dark:bg-slate-800/95 {{ $palette['border'] }} border-white/40 dark:border-slate-700/60"
                        >
                            <div
                                class="pointer-events-none absolute -end-6 -top-6 h-24 w-24 rounded-full bg-gradient-to-br opacity-15 blur-2xl transition group-hover:opacity-25 {{ $palette['glow'] }}"
                                aria-hidden="true"
                            ></div>
                            <div class="relative flex items-start gap-3 sm:gap-4">
                                @if ($avatarUrl)
                                    <img
                                        src="{{ $avatarUrl }}"
                                        alt=""
                                        class="h-11 w-11 shrink-0 rounded-2xl object-cover sm:h-12 sm:w-12"
                                        loading="lazy"
                                        decoding="async"
                                    />
                                @else
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br text-sm font-bold text-white sm:h-12 sm:w-12 sm:text-base {{ $palette['avatar'] }}"
                                        aria-hidden="true"
                                    >
                                        {{ $initial }}
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <h3 class="truncate text-sm font-bold text-gray-900 sm:text-base dark:text-gray-50">
                                        {{ $displayName }}
                                    </h3>
                                    <p class="mt-0.5 text-xs text-gray-500 sm:text-sm dark:text-gray-400">
                                        {{ $comment->created_at->diffForHumans() }}
                                    </p>
                                    @if (! $post && $comment->commentable instanceof \Miran\Mksine\Models\Post)
                                        <p class="mt-1 truncate text-xs font-medium text-violet-600/90 dark:text-violet-400/90">
                                            <a
                                                href="{{ route('posts.show', $comment->commentable->slug) }}#comments-section"
                                                class="hover:underline"
                                            >{{ $comment->commentable->title }}</a>
                                        </p>
                                    @endif
                                </div>
                            </div>
                            @if ($comment->hasRating())
                                <div
                                    class="relative mt-3 flex gap-0.5"
                                    aria-label="{{ __('mksine::page_builder.comments_feed_rating_stars', ['count' => $comment->rating]) }}"
                                >
                                    @for ($i = 1; $i <= 5; $i++)
                                        <span class="text-base {{ $i <= $comment->rating ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600' }}">★</span>
                                    @endfor
                                </div>
                            @endif
                            <blockquote class="relative mt-3 flex-1 sm:mt-4">
                                <p class="text-pretty text-sm leading-relaxed text-gray-700 line-clamp-5 dark:text-gray-300">
                                    “{{ $quoteVisible }}”
                                </p>
                            </blockquote>
                            @if ($replyCount > 0 && $targetPost)
                                <p class="relative mt-3 text-xs text-gray-500 dark:text-gray-400">
                                    <a
                                        href="{{ route('posts.show', $targetPost->slug) }}#comments-section"
                                        class="font-medium text-violet-600 hover:underline dark:text-violet-400"
                                    >{{ trans_choice('mksine::page_builder.comments_feed_reply_count', $replyCount) }}</a>
                                </p>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</section>
