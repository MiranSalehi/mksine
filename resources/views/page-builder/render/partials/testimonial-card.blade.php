@php
    $content = $testimonial['content'] ?? '';
    $authorName = $testimonial['author_name'] ?? '';
    $authorTitle = $testimonial['author_title'] ?? '';
    $authorImageId = $testimonial['author_image'] ?? null;
    $rating = (int) ($testimonial['rating'] ?? 0);

    $authorImageUrl = null;
    if ($authorImageId) {
        $media = \Miran\Mksine\Models\Media::find($authorImageId);
        $authorImageUrl = $media?->url;
    }
@endphp

<div class="{{ $cardClasses }}">
    {{-- Quote icon for quote style --}}
    @if($style === 'quote')
        <svg class="w-8 h-8 text-pink-500/30 mb-4" fill="currentColor" viewBox="0 0 32 32">
            <path d="M10 8c-3.3 0-6 2.7-6 6v10h10V14H6c0-2.2 1.8-4 4-4V8zm14 0c-3.3 0-6 2.7-6 6v10h10V14h-8c0-2.2 1.8-4 4-4V8z"/>
        </svg>
    @endif

    {{-- Rating --}}
    @if($rating > 0)
        <div class="flex gap-1 mb-4">
            @for($i = 1; $i <= 5; $i++)
                <svg class="w-5 h-5 {{ $i <= $rating ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
            @endfor
        </div>
    @endif

    {{-- Content --}}
    @if($content)
        <p class="text-gray-700 dark:text-gray-300 mb-6 leading-relaxed">
            "{{ $content }}"
        </p>
    @endif

    {{-- Author --}}
    <div class="flex items-center gap-4">
        @if($authorImageUrl)
            <img src="{{ asset($authorImageUrl) }}" alt="{{ $authorName }}" class="w-12 h-12 rounded-full object-cover">
        @else
            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center text-white font-bold">
                {{ strtoupper(substr($authorName, 0, 1)) }}
            </div>
        @endif

        <div>
            @if($authorName)
                <p class="font-semibold text-gray-900 dark:text-white">{{ $authorName }}</p>
            @endif
            @if($authorTitle)
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $authorTitle }}</p>
            @endif
        </div>
    </div>
</div>
