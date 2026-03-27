<div>
     @themeDoAction('home.before_hero')
     <!-- Hero Section -->
     <section class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-12">
        <div class="container mx-auto max-w-6xl px-4">
            <div class="max-w-2xl">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Welcome to Health Blog</h1>
                <p class="text-lg text-blue-100">Useful articles about health, wellness and beauty</p>
            </div>
        </div>
    </section>
    @themeDoAction('home.after_hero')

    <!-- Main Content -->
    <div class="container mx-auto max-w-6xl px-4 py-12">
        @themeDoAction('home.before_section_latest')
        <!-- Latest Articles -->
        <section class="mb-16">
            <h2 class="text-3xl font-bold text-gray-800 mb-8">Latest Articles</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($latestPosts as $post)
                <article class="bg-white rounded-lg overflow-hidden shadow hover:shadow-lg transition">
                    <a href="{{ route('posts.show', $post->slug) }}">
                        <div class="h-48 bg-gradient-to-br from-slate-200 to-slate-300 flex items-center justify-center">
                            @if ($post->featuredImage?->url)
                                <img src="{{ asset($post->featuredImage?->url) }}" alt="{{ $post->title }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <x-heroicon-o-photo class="w-full h-full text-gray-300 dark:text-gray-600" />
                                </div>
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">{{ $post->title }}</h3>
                            <p class="text-gray-600 text-sm mb-4">{{ $post->excerpt }}</p>
                            <div class="flex justify-between items-center text-xs text-gray-500">
                                <span><a href="{{ route('authors.show', $post->author->id) }}" class="hover:text-blue-500">Author: {{ $post->author->name }}</a></span>
                                <span>{{ $post->published_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </a>
                </article>
                @endforeach
            
            </div>
        </section>
        @themeDoAction('home.after_section_latest')


        @themeDoAction('home.before_section_categories')
        <!-- Categories Section -->
        <section class="mb-16">
            <h2 class="text-3xl font-bold text-gray-800 mb-8">Categories</h2>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach ($categories as $category)
                    <a href="{{ $category->getUrl() }}" class="bg-white border-2 border-gray-200 hover:border-blue-500 rounded-lg p-6 text-center transition">
                        <h3 class="font-semibold text-gray-800">{{ $category->name }}</h3>
                    </a>
                @endforeach
            </div>
        </section>
        @themeDoAction('home.after_section_categories')


    </div>

</div>