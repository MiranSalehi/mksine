{{--
    Responsive media image: WebP copies in srcset (when generated), the original as fallback,
    intrinsic width/height (no layout shift) and lazy loading unless `priority` is set.

    <x-mksine::image :media="$post->featuredImage" sizes="(min-width: 1024px) 33vw, 100vw" class="h-full w-full object-cover" />
    <x-mksine::image :media="$hero" :priority="true" alt="…" />
--}}
@props([
    'media' => null,
    'alt' => null,
    'sizes' => '100vw',
    'priority' => false,
])

@php
    /** @var \Miran\Mksine\Models\Media|null $media */
    $src = $media?->url ? asset($media->url) : ($media?->path ? $media->full_url : null);
    $srcset = $media?->srcset();
    $width = $media?->width ?: null;
    $height = $media?->height ?: null;
@endphp

@if ($src)
    @if ($srcset)
        <picture>
            <source type="image/webp" srcset="{{ $srcset }}" sizes="{{ $sizes }}">
            <img
                src="{{ $src }}"
                alt="{{ $alt ?? ($media->alt ?? '') }}"
                @if ($width && $height) width="{{ $width }}" height="{{ $height }}" @endif
                loading="{{ $priority ? 'eager' : 'lazy' }}"
                decoding="{{ $priority ? 'auto' : 'async' }}"
                @if ($priority) fetchpriority="high" @endif
                {{ $attributes }}
            >
        </picture>
    @else
        <img
            src="{{ $src }}"
            alt="{{ $alt ?? ($media->alt ?? '') }}"
            @if ($width && $height) width="{{ $width }}" height="{{ $height }}" @endif
            loading="{{ $priority ? 'eager' : 'lazy' }}"
            decoding="{{ $priority ? 'auto' : 'async' }}"
            @if ($priority) fetchpriority="high" @endif
            {{ $attributes }}
        >
    @endif
@endif
