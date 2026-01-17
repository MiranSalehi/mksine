<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold">{{ $category->name }}</h1>
        @if($category->description)
            <p class="text-gray-600 mt-2">{{ $category->description }}</p>
        @endif
        <div class="text-sm text-gray-500 mt-2">
            <a href="{{ route('categories.index') }}" class="hover:underline">&larr; Back to Categories</a>
        </div>
    </div>

    @if($posts->count())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($posts as $post)
                <div class="border rounded-lg p-4 shadow hover:shadow-lg transition">
                    <h2 class="text-xl font-semibold mb-2">
                        <a href="{{ route('posts.show', $post->slug) }}" class="hover:underline">
                            {{ $post->title }}
                        </a>
                    </h2>
                    <p class="text-gray-600 mb-4">{{ Str::limit($post->excerpt, 100) }}</p>
                    <div class="text-sm text-gray-500">
                        {{ $post->published_at?->format('F j, Y') }}
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="mt-6">
            {{ $posts->links() }}
        </div>
    @else
        <p class="text-gray-500">No posts found in this category.</p>
    @endif
</div>
