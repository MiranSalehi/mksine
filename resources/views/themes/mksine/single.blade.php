<div>
    @themeDoAction('single.before_breadcrumb')
    <!-- Breadcrumb (parent → child, WordPress-style) -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="container mx-auto max-w-6xl px-4 py-3">
            <div class="text-sm text-gray-600 dark:text-gray-400 flex flex-wrap items-center gap-x-2 gap-y-1">
                <a href="{{ route('home') }}" class="text-blue-500 hover:text-blue-600">{{ __('mksine::frontend.home') }}</a>
                @php
                    $primaryCategory = $post->categories->first();
                    $categoryPath = $primaryCategory ? $primaryCategory->getBreadcrumbPath() : collect();
                @endphp
                @foreach($categoryPath as $cat)
                    <span class="text-gray-400 dark:text-gray-500" aria-hidden="true">/</span>
                    <a href="{{ $cat->getUrl() }}" class="text-blue-500 hover:text-blue-600">{{ $cat->name }}</a>
                @endforeach
                @if($categoryPath->isNotEmpty())
                    <span class="text-gray-400 dark:text-gray-500" aria-hidden="true">/</span>
                @else
                    <span class="text-gray-400 dark:text-gray-500" aria-hidden="true">/</span>
                    <a href="{{ route('categories.index') }}" class="text-blue-500 hover:text-blue-600">{{ __('mksine::frontend.categories') }}</a>
                    <span class="text-gray-400 dark:text-gray-500" aria-hidden="true">/</span>
                @endif
                <span class="text-gray-800 dark:text-gray-200">{{ $post->title }}</span>
            </div>
        </div>
    </div>
    @themeDoAction('single.after_breadcrumb')

    @themeDoAction('single.before_content')
    <!-- Main Content -->
    <div class="container mx-auto max-w-4xl px-4 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Article -->
            <article class="lg:col-span-2">
                <!-- Article Header -->
                <div class="mb-8">
                    <h1 class="text-4xl font-bold text-gray-800 mb-4">
                        {{ $post->title }}
                    </h1>

                    <!-- Article Meta -->
                    <div class="flex flex-wrap gap-6 text-sm text-gray-600">
                        <div class="flex items-center gap-2">
                            <div>
                                <p class="font-semibold text-gray-800">
                                    <a href="{{ route('authors.show', $post->author->id) }}" class="hover:text-blue-500">{{ $post->author->name }}</a>
                                </p>
                                <p class="text-xs">{{ $post->author->role ?? 'Author' }}</p>
                            </div>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">{{ $post->published_at->format('M d, Y') }}</p>
                            <p class="text-xs">Published Date</p>
                        </div>
                    </div>
                </div>

                <!-- Featured Image -->
                <div class="mb-8">
                    <img src="{{ asset($post->featuredImage?->url) }}" alt="{{ $post->title }}" class="w-full rounded-lg shadow-lg">
                </div>

                <!-- Article Content -->
                <div class="prose prose-lg max-w-none mb-8">
                    {!! $post->content !!}
                </div>

                <!-- Social Share -->
                <div class="bg-gray-100 p-6 rounded-lg mb-8">
                    <h3 class="font-bold text-gray-800 mb-4">{{ __('mksine::frontend.share_this_article') }}</h3>
                    <div class="flex gap-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('posts.show', $post->slug)) }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Facebook</a>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('posts.show', $post->slug)) }}&text={{ urlencode($post->title) }}" class="bg-blue-400 text-white px-4 py-2 rounded hover:bg-blue-500 transition">Twitter</a>
                        <a href="https://www.instagram.com/share?url={{ urlencode(route('posts.show', $post->slug)) }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Instagram</a>
                        <a href="javascript:void(0)" onclick="copyToClipboard('{{ urlencode(route('posts.show', $post->slug)) }}')" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition">Copy Link</a>
                    </div>
                </div>

                <!-- Author Bio -->
                <div class="bg-white border-2 border-gray-200 p-6 rounded-lg mb-8">
                    <h3 class="font-bold text-gray-800 mb-4">{{ __('mksine::frontend.about_the_author') }}</h3>
                    <div class="flex gap-4">
                        @if($post->author->avatar_url)
                            <img src="{{ $post->author->avatar_url }}" alt="{{ $post->author->name }}" class="w-20 h-20 rounded-full object-cover">
                        @else
                            <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center text-2xl font-bold text-blue-600">
                                {{ $post->author->initials() }}
                            </div>
                        @endif
                        <div>
                            <h4 class="font-bold text-gray-800 mb-2">{{ $post->author->name }}</h4>
                            @if($post->author->bio)
                                <p class="text-gray-600 text-sm mb-3">{{ $post->author->bio }}</p>
                            @endif
                            <a href="{{ route('authors.show', $post->author->id) }}" class="text-blue-500 hover:text-blue-600 font-semibold text-sm">{{ __('mksine::frontend.view_all_articles_by_author') }}</a>
                        </div>
                    </div>
                </div>

                <!-- Comments Section (dynamic) -->
                @livewire('mksine::frontend.post-comments', ['postId' => $post->id])
            </article>
            @themeDoAction('single.after_article')

            <!-- Sidebar -->
            <aside class="lg:col-span-1">
                <!-- Recent Articles -->
                <div class="bg-white p-6 rounded-lg shadow mb-6">
                    <h3 class="font-bold text-gray-800 mb-4">Recent Articles</h3>
                    <ul class="space-y-4">
                        <li>
                            <a href="#" class="text-blue-500 hover:text-blue-600 font-semibold">Treatment of Bone Fractures</a>
                            <p class="text-xs text-gray-500 mt-1">December 3, 2023</p>
                        </li>
                        <li>
                            <a href="#" class="text-blue-500 hover:text-blue-600 font-semibold">Best Exercises for Health</a>
                            <p class="text-xs text-gray-500 mt-1">December 1, 2023</p>
                        </li>
                        <li>
                            <a href="#" class="text-blue-500 hover:text-blue-600 font-semibold">Balanced Diet Guide</a>
                            <p class="text-xs text-gray-500 mt-1">November 29, 2023</p>
                        </li>
                    </ul>
                </div>

                <!-- Categories -->
                <div class="bg-white p-6 rounded-lg shadow mb-6">
                    <h3 class="font-bold text-gray-800 mb-4">Categories</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-blue-500 hover:text-blue-600">Medicine (24)</a></li>
                        <li><a href="#" class="text-blue-500 hover:text-blue-600">Nutrition (18)</a></li>
                        <li><a href="#" class="text-blue-500 hover:text-blue-600">Exercise (15)</a></li>
                        <li><a href="#" class="text-blue-500 hover:text-blue-600">Beauty (12)</a></li>
                    </ul>
                </div>

               
            </aside>
        </div>
        @themeDoAction('single.after_content')

        @themeDoAction('single.before_related')
        <!-- Related Articles (same style as home Latest Articles) -->
        @if($relatedPosts->isNotEmpty())
        <section class="mt-16">
            <h2 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-8">{{ __('mksine::frontend.related_articles') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($relatedPosts as $related)
                <article class="rounded-lg overflow-hidden shadow hover:shadow-lg transition bg-white dark:bg-gray-800">
                    <a href="{{ route('posts.show', $related->slug) }}" class="block">
                        <div class="h-48 bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-600 dark:to-slate-700 flex items-center justify-center overflow-hidden">
                            @if($related->featuredImage?->url)
                                <img src="{{ asset($related->featuredImage->url) }}" alt="{{ $related->title }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <x-heroicon-o-photo class="w-16 h-16 text-gray-400 dark:text-gray-500" />
                                </div>
                            @endif
                        </div>
                        <div class="p-4 bg-white dark:bg-gray-800">
                            <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">{{ $related->title }}</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">{{ $related->excerpt }}</p>
                            <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
                                <span><a href="{{ route('authors.show', $related->author->id) }}" class="hover:text-blue-500 text-gray-600 dark:text-gray-400" onclick="event.stopPropagation();">{{ __('mksine::frontend.author') }}: {{ $related->author->name }}</a></span>
                                <span class="text-gray-500 dark:text-gray-400">{{ $related->published_at?->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </a>
                </article>
                @endforeach
            </div>
        </section>
        @themeDoAction('single.after_related')
        @endif
    </div>
</div>