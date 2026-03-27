<div>
    @themeDoAction('category.before_breadcrumb')
    <!-- Breadcrumb -->
    <div class="bg-white border-b border-gray-200">
        <div class="container mx-auto max-w-6xl px-4 py-3">
            <div class="text-sm text-gray-600">
                <a href="{{ route('home') }}" class="text-blue-500 hover:text-blue-600">{{ __('mksine::frontend.home') }}</a>
                <span class="mx-2">/</span>
                <a href="{{ route('categories.index') }}" class="text-blue-500 hover:text-blue-600">{{ __('mksine::frontend.categories') }}</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800">{{ $category->name }}</span>
            </div>
        </div>
    </div>
    @themeDoAction('category.after_breadcrumb')

    @themeDoAction('category.before_header')
    <!-- Category Header -->
    <section class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-12">
        <div class="container mx-auto max-w-6xl px-4">
            <h1 class="text-4xl font-bold mb-2">{{ $category->name }}</h1>
            @if($category->description)
                <p class="text-blue-100">{{ $category->description }}</p>
            @endif
            <p class="text-sm text-blue-100 mt-4">{{ $posts->total() }} {{ __('mksine::frontend.articles') }}</p>
        </div>
    </section>
    @themeDoAction('category.after_header')

    @themeDoAction('category.before_content')
    <!-- Main Content -->
    <div class="container mx-auto max-w-6xl px-4 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Articles Grid -->
            <div class="lg:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse($posts as $post)
                        <article class="bg-white rounded-lg overflow-hidden shadow hover:shadow-lg transition">
                            <a href="{{ route('posts.show', $post->slug) }}">
                                <div class="relative h-48 overflow-hidden bg-gray-100 flex items-center justify-center">
                                    @if($post->featuredImage)
                                        <img src="{{ $post->featuredImage->full_url }}" alt="{{ $post->title }}" class="absolute inset-0 block w-full h-full object-cover object-center">
                                    @else
                                        <x-heroicon-o-photo class="w-16 h-16 text-gray-400" />
                                    @endif
                                </div>
                                <div class="p-4">
                                    <h3 class="font-bold text-gray-800 mb-2 hover:text-blue-500 transition">{{ $post->title }}</h3>
                                    <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ $post->excerpt }}</p>
                                    <div class="flex justify-between items-center text-xs text-gray-500">
                                        <a href="{{ route('authors.show', $post->author->id) }}" class="hover:text-blue-500">{{ $post->author->name }}</a>
                                        <span>{{ $post->published_at?->format('M d, Y') }}</span>
                                    </div>
                                </div>
                            </a>
                        </article>
                    @empty
                        <p class="text-gray-500 col-span-full">{{ __('mksine::frontend.no_articles_in_category') }}</p>
                    @endforelse
                </div>

                @if($posts->hasPages())
                    <div class="mt-12 flex justify-center">
                        {{ $posts->onEachSide(1)->links('mksine::components.pagination') }}
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <aside class="lg:col-span-1">
                <!-- Search -->
                <div class="bg-white p-6 rounded-lg shadow mb-6">
                    <h3 class="font-bold text-gray-800 mb-4">{{ __('mksine::frontend.search') }}</h3>
                    <form action="{{ route('posts.index') }}" method="GET" class="flex">
                        <input type="text" name="search" placeholder="{{ __('mksine::frontend.search_placeholder') }}" class="flex-1 px-4 py-2 border border-gray-300 rounded-l focus:outline-none focus:border-blue-500">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-r hover:bg-blue-600 transition">&#128269;</button>
                    </form>
                </div>

                <!-- Categories -->
                <div class="bg-white p-6 rounded-lg shadow mb-6">
                    <h3 class="font-bold text-gray-800 mb-4">{{ __('mksine::frontend.categories') }}</h3>
                    <ul class="space-y-3">
                        @foreach($categories as $cat)
                            <li>
                                <a href="{{ $cat->getUrl() }}" class="text-blue-500 hover:text-blue-600 font-semibold flex justify-between {{ $cat->id === $category->id ? 'underline' : '' }}">
                                    <span>{{ $cat->name }}</span>
                                    <span class="text-gray-500 font-normal">({{ $cat->posts_count }})</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

             
            </aside>
        </div>
    </div>
    @themeDoAction('category.after_content')
</div>
