<div>
    <h1 class="text-3xl font-bold mb-6">Latest Posts</h1>
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
</div>
