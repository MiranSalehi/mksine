<div>
    <article class="max-w-4xl mx-auto">
        <header class="mb-8">
            <h1 class="text-4xl font-bold mb-4">{{ $post->title }}</h1>
            <div class="flex items-center text-gray-500">
                <span>{{ $post->published_at?->format('F j, Y') }}</span>
                @if($post->author)
                    <span class="mx-2">•</span>
                    <span>By {{ $post->author->name }}</span>
                @endif
                @if($post->categories->count())
                    <span class="mx-2">•</span>
                    <div class="flex gap-2">
                        @foreach($post->categories as $category)
                            <a href="{{ route('categories.show', $category->slug) }}" class="text-blue-600 hover:underline">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </header>

        @if($post->featured_image)
             {{-- Assuming you have a way to display media, for now generic placeholder or check if Media model has url --}}
            <div class="mb-8">
                 @php
                     $media = \Miran\Mksine\Models\Media::find($post->featured_image);
                 @endphp
                 @if($media)
                    <img src="{{ $media->url }}" alt="{{ $post->title }}" class="w-full rounded-lg shadow-lg">
                 @endif
            </div>
        @endif

        <div class="prose max-w-none">
            {!! $post->content !!}
        </div>
        
        <footer class="mt-12 pt-8 border-t">
             <a href="{{ route('posts.index') }}" class="text-blue-600 hover:underline">&larr; Back to all posts</a>
        </footer>
    </article>
</div>
