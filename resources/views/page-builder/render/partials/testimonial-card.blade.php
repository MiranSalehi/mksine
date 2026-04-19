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
    @if ($style === 'quote')
        <svg class="mb-4 h-8 w-8 text-amber-500/35 dark:text-amber-400/30" fill="currentColor" viewBox="0 0 32 32" aria-hidden="true">
            <path d="M10 8c-3.3 0-6 2.7-6 6v10h10V14H6c0-2.2 1.8-4 4-4V8zm14 0c-3.3 0-6 2.7-6 6v10h10V14h-8c0-2.2 1.8-4 4-4V8z"/>
        </svg>
    @endif

    @if ($rating > 0)
        <div class="mb-4 flex gap-0.5">
            @for ($i = 1; $i <= 5; $i++)
                <svg class="h-5 w-5 {{ $i <= $rating ? 'text-amber-400' : 'text-zinc-200 dark:text-zinc-600' }}" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
            @endfor
        </div>
    @endif

    @if ($content)
        <p class="mb-6 text-base leading-relaxed text-zinc-700 dark:text-zinc-300">
            “{{ $content }}”
        </p>
    @endif

    <div class="flex items-center gap-4">
        @if ($authorImageUrl)
            <img src="{{ asset($authorImageUrl) }}" alt="{{ $authorName }}" class="h-12 w-12 rounded-full object-cover ring-2 ring-white shadow-sm dark:ring-zinc-800">
        @else
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-amber-500 to-orange-600 text-sm font-bold text-white shadow-md">
                {{ strtoupper(substr($authorName, 0, 1)) }}
            </div>
        @endif

        <div class="min-w-0">
            @if ($authorName)
                <p class="font-semibold text-zinc-900 dark:text-zinc-50">{{ $authorName }}</p>
            @endif
            @if ($authorTitle)
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $authorTitle }}</p>
            @endif
        </div>
    </div>
</div>
