@php
    $variant = $variant ?? 'grid';
    $src = Storage::disk($media->disk)->url($media->path);
@endphp

@if($media->isImage() && ! str_starts_with($media->mime_type ?? '', 'image/svg'))
    <img
        src="{{ $src }}"
        alt="{{ $media->name }}"
        @class([
            'h-full w-full object-cover' => $variant === 'grid',
            'transition-transform duration-200 group-hover:scale-105' => $variant === 'grid',
            'aspect-video w-full bg-gray-50 object-contain dark:bg-gray-900' => $variant === 'detail',
        ])
        @if($variant === 'grid') loading="lazy" @endif
    >
@elseif($media->isImage())
    <div @class([
        'flex h-full w-full items-center justify-center bg-white p-2 dark:bg-gray-800' => $variant === 'grid',
        'flex aspect-video items-center justify-center bg-white p-4 dark:bg-gray-900' => $variant === 'detail',
    ])>
        <img
            src="{{ $src }}"
            alt="{{ $media->name }}"
            class="max-h-full max-w-full object-contain"
            @if($variant === 'grid') loading="lazy" @endif
        >
    </div>
@elseif($media->isVideo())
    <div @class([
        'relative h-full w-full bg-black' => $variant === 'grid',
        'overflow-hidden bg-black' => $variant === 'detail',
    ])>
        <video
            src="{{ $src }}"
            @class([
                'pointer-events-none h-full w-full object-cover' => $variant === 'grid',
                'aspect-video w-full' => $variant === 'detail',
            ])
            @if($variant === 'grid') muted preload="metadata" playsinline @else controls preload="metadata" playsinline @endif
        ></video>
        @if($variant === 'grid')
            <div class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/25">
                <x-heroicon-s-play class="h-8 w-8 text-white/90 drop-shadow" />
            </div>
        @endif
    </div>
@elseif($media->isAudio())
    <div @class([
        'flex h-full w-full flex-col items-center justify-center gap-1 bg-gradient-to-br from-sky-50 to-indigo-100 px-2 dark:from-sky-950 dark:to-indigo-950' => $variant === 'grid',
        'space-y-3 bg-gradient-to-br from-sky-50 to-indigo-100 p-4 dark:from-sky-950 dark:to-indigo-950' => $variant === 'detail',
    ])>
        <x-heroicon-o-musical-note @class([
            'h-10 w-10 text-sky-500 dark:text-sky-400' => $variant === 'grid',
            'mx-auto h-10 w-10 text-sky-500 dark:text-sky-400' => $variant === 'detail',
        ]) />
        @if($variant === 'grid')
            <span class="max-w-full truncate text-[10px] font-medium text-sky-800 dark:text-sky-200">{{ $media->name }}</span>
        @else
            <audio src="{{ $src }}" class="w-full" controls preload="metadata"></audio>
        @endif
    </div>
@else
    <div @class([
        'flex h-full w-full items-center justify-center bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800' => $variant === 'grid',
        'flex aspect-video items-center justify-center bg-gray-100 dark:bg-gray-800' => $variant === 'detail',
    ])>
        <x-heroicon-o-document @class([
            'h-10 w-10 text-gray-400 dark:text-gray-500' => $variant === 'grid',
            'h-14 w-14 text-gray-400 dark:text-gray-500' => $variant === 'detail',
        ]) />
    </div>
@endif
