<div>
    <!-- Breadcrumb -->
    <div class="bg-white border-b border-gray-200">
        <div class="container mx-auto max-w-6xl px-4 py-3">
            <div class="text-sm text-gray-600">
                <a href="{{ route('home') }}" class="text-pink-500 hover:text-pink-600">{{ __('Home') }}</a>
                <span class="mx-2">/</span>
                <a href="{{ route('categories.index') }}" class="text-pink-500 hover:text-pink-600">{{ __('Categories') }}</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800">{{ $category->name }}</span>
            </div>
        </div>
    </div>

    <!-- Category Header -->
    <section class="bg-gradient-to-r from-pink-500 to-red-400 text-white py-12">
        <div class="container mx-auto max-w-6xl px-4">
            <h1 class="text-4xl font-bold mb-2">{{ $category->name }}</h1>
            @if($category->description)
                <p class="text-pink-100">{{ $category->description }}</p>
            @endif
            <p class="text-sm text-pink-100 mt-4">{{ $posts->total() }} {{ __('Articles') }}</p>
        </div>
    </section>

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
                                    <h3 class="font-bold text-gray-800 mb-2 hover:text-pink-500 transition">{{ $post->title }}</h3>
                                    <p class="text-gray-600 text-sm mb-3 line-clamp-2">{{ $post->excerpt }}</p>
                                    <div class="flex justify-between items-center text-xs text-gray-500">
                                        <a href="{{ route('authors.show', $post->author->id) }}" class="hover:text-pink-500">{{ $post->author->name }}</a>
                                        <span>{{ $post->published_at?->format('M d, Y') }}</span>
                                    </div>
                                </div>
                            </a>
                        </article>
                    @empty
                        <p class="text-gray-500 col-span-full">{{ __('No articles in this category yet.') }}</p>
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
                    <h3 class="font-bold text-gray-800 mb-4">{{ __('Search') }}</h3>
                    <form action="{{ route('posts.index') }}" method="GET" class="flex">
                        <input type="text" name="search" placeholder="{{ __('Search...') }}" class="flex-1 px-4 py-2 border border-gray-300 rounded-l focus:outline-none focus:border-pink-500">
                        <button type="submit" class="bg-pink-500 text-white px-4 py-2 rounded-r hover:bg-pink-600 transition">&#128269;</button>
                    </form>
                </div>

                <!-- Categories -->
                <div class="bg-white p-6 rounded-lg shadow mb-6">
                    <h3 class="font-bold text-gray-800 mb-4">{{ __('Categories') }}</h3>
                    <ul class="space-y-3">
                        @foreach($categories as $cat)
                            <li>
                                <a href="{{ $cat->getUrl() }}" class="text-pink-500 hover:text-pink-600 font-semibold flex justify-between {{ $cat->id === $category->id ? 'underline' : '' }}">
                                    <span>{{ $cat->name }}</span>
                                    <span class="text-gray-500 font-normal">({{ $cat->posts_count }})</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Newsletter -->
                <div class="bg-gradient-to-r from-pink-500 to-red-400 text-white p-6 rounded-lg shadow">
                    <h3 class="font-bold mb-3">{{ __('Newsletter') }}</h3>
                    <p class="text-sm mb-4">{{ __('Get the Latest Updates') }}</p>
                    <form class="space-y-3">
                        <input type="email" placeholder="{{ __('Email') }}" class="w-full px-3 py-2 rounded text-gray-800 focus:outline-none text-sm">
                        <button type="submit" class="w-full bg-white text-pink-500 px-3 py-2 rounded font-semibold hover:bg-pink-50 transition text-sm">{{ __('Subscribe') }}</button>
                    </form>
                </div>
            </aside>
        </div>
    </div>
</div>
