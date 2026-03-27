<div>
        @themeDoAction('author.before_breadcrumb')
        <!-- Breadcrumb -->
        <div class="bg-white border-b border-gray-200">
            <div class="container mx-auto max-w-6xl px-4 py-3">
                <div class="text-sm text-gray-600">
                    <a href="{{ route('home') }}" class="text-blue-500 hover:text-blue-600">{{ __('mksine::frontend.home') }}</a>
                    <span class="mx-2">/</span>
                    <span class="text-gray-800">{{ __('mksine::frontend.author') }}: {{ $author->name }}</span>
                </div>
            </div>
        </div>
        @themeDoAction('author.after_breadcrumb')

        @themeDoAction('author.before_header')
        <!-- Author Profile Section -->
        <section class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-12">
            <div class="container mx-auto max-w-6xl px-4">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <div class="flex-shrink-0">
                        @if($author->avatar_url)
                            <img src="{{ $author->avatar_url }}" alt="{{ $author->name }}" class="w-40 h-40 rounded-full border-4 border-white shadow-lg object-cover">
                        @else
                            <div class="w-40 h-40 rounded-full border-4 border-white shadow-lg bg-white/20 flex items-center justify-center text-5xl font-bold">
                                {{ $author->initials() }}
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <h1 class="text-4xl font-bold mb-2">{{ $author->name }}</h1>
                        @if($author->bio)
                            <p class="text-blue-100 max-w-2xl mb-6">{{ $author->bio }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </section>
        @themeDoAction('author.after_header')

        @themeDoAction('author.before_content')
        <!-- Main Content -->
        <div class="container mx-auto max-w-6xl px-4 py-12">
            <!-- Author Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
                <div class="bg-white p-6 rounded-lg shadow text-center">
                    <div class="text-3xl font-bold text-blue-500 mb-2">{{ $posts->total() }}</div>
                    <p class="text-gray-600">{{ __('mksine::frontend.articles') }}</p>
                </div>
            </div>

            @themeDoAction('author.before_articles')
            <!-- Articles Section -->
            <section>
                <h2 class="text-3xl font-bold text-gray-800 mb-8">{{ $author->name }}'s {{ __('mksine::frontend.articles') }}</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                    @forelse($posts as $post)
                        <article class="bg-white rounded-lg overflow-hidden shadow hover:shadow-lg transition">
                            <a href="{{ route('posts.show', $post->slug) }}">
                                <div class="h-48 bg-gradient-to-br from-slate-200 to-slate-300 flex items-center justify-center">
                                    @if($post->featuredImage)
                                        <img src="{{ $post->featuredImage->full_url }}" alt="{{ $post->title }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center">
                                            <x-heroicon-o-photo class="w-16 h-16 text-gray-400" />
                                        </div>
                                    @endif
                                </div>
                                <div class="p-4">
                                    <h3 class="font-bold text-gray-800 mb-2 hover:text-blue-500 transition">{{ $post->title }}</h3>
                                    <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ $post->excerpt }}</p>
                                    <div class="flex justify-between items-center text-xs text-gray-500">
                                        <span>{{ $post->published_at?->format('M d, Y') }}</span>
                                    </div>
                                </div>
                            </a>
                        </article>
                    @empty
                        <p class="text-gray-500 col-span-full">{{ __('mksine::frontend.no_articles_yet') }}</p>
                    @endforelse
                </div>

                @if($posts->hasPages())
                    <div class="mt-8">
                        {{ $posts->onEachSide(1)->links('mksine::components.pagination') }}
                    </div>
                @endif
            </section>
            @themeDoAction('author.after_articles')
        </div>
        @themeDoAction('author.after_content')
    </div>
</div>
