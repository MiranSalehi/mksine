<div>
    @themeDoAction('posts.before_breadcrumb')
    <!-- Breadcrumb -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="container mx-auto max-w-6xl px-4 py-3">
            <div class="text-sm text-gray-600 dark:bg-gray-400">
                <a href="{{ route('home') }}" class="text-blue-500 hover:text-blue-600">{{ __('mksine::frontend.home') }}</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800 dark:text-gray-200">{{ __('mksine::frontend.all_posts') }}</span>
            </div>
        </div>
    </div>
    @themeDoAction('posts.after_breadcrumb')

    @themeDoAction('posts.before_header')
    <!-- Page Header -->
    <section class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-12">
        <div class="container mx-auto max-w-6xl px-4">
            <h1 class="text-4xl md:text-5xl font-bold mb-2">{{ __('mksine::frontend.all_posts') }}</h1>
            <p class="text-blue-100">{{ $posts->total() }} {{ __('mksine::frontend.articles') }}</p>
        </div>
    </section>
    @themeDoAction('posts.after_header')

    @themeDoAction('posts.before_content')
    <!-- Main Content -->
    <div class="container mx-auto max-w-6xl px-4 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @forelse($posts as $post)
                        <article class="bg-white dark:bg-gray-800 rounded-lg overflow-hidden shadow hover:shadow-lg transition">
                            <a href="{{ route('posts.show', $post->slug) }}">
                                <div class="relative h-48 overflow-hidden bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                    @if($post->featuredImage)
                                        <img src="{{ $post->featuredImage->full_url }}" alt="{{ $post->title }}" class="absolute inset-0 block w-full h-full object-cover object-center">
                                    @else
                                        <x-heroicon-o-photo class="w-16 h-16 text-gray-400" />
                                    @endif
                                </div>
                                <div class="p-4">
                                    <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-2 hover:text-blue-500 transition">{{ $post->title }}</h3>
                                    <p class="text-gray-600 dark:text-gray-400 text-sm mb-3 line-clamp-2">{{ $post->excerpt }}</p>
                                    <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
                                        <a href="{{ route('authors.show', $post->author->id) }}" class="hover:text-blue-500">{{ $post->author->name }}</a>
                                        <span>{{ $post->published_at?->format('M d, Y') }}</span>
                                    </div>
                                </div>
                            </a>
                        </article>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 col-span-full">{{ __('mksine::frontend.no_posts_yet') }}</p>
                    @endforelse
                </div>

                @if($posts->hasPages())
                    <div class="mt-12 flex justify-center">
                        {{ $posts->onEachSide(1)->links('mksine::components.pagination') }}
                    </div>
                @endif
            </div>

            <aside class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow mb-6">
                    <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-4">{{ __('mksine::frontend.categories') }}</h3>
                    <ul class="space-y-3">
                        @foreach($categories as $cat)
                            <li>
                                <a href="{{ $cat->getUrl() }}" class="text-blue-500 hover:text-blue-600 font-semibold flex justify-between">
                                    <span>{{ $cat->name }}</span>
                                    <span class="text-gray-500 dark:text-gray-400 font-normal">({{ $cat->posts_count }})</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        </div>
    </div>
    @themeDoAction('posts.after_content')
</div>
