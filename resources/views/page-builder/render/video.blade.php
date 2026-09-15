@php
    $videoId = $data['video'] ?? null;
    $caption = $data['caption'] ?? '';
    $controls = (bool) ($data['controls'] ?? true);
    $autoplay = (bool) ($data['autoplay'] ?? false);
    $loop = (bool) ($data['loop'] ?? false);

    $media = $videoId ? \Miran\Mksine\Models\Media::query()->find($videoId) : null;
    $src = $media?->full_url;
@endphp

@if ($src && $media?->isVideo())
    <figure class="mb-8 md:mb-10">
        <video
            class="h-auto w-full rounded-lg bg-black ring-1 ring-slate-900/5 dark:ring-white/10"
            src="{{ $src }}"
            @if($controls) controls @endif
            @if($autoplay) autoplay muted playsinline @endif
            @if($loop) loop @endif
            preload="metadata"
        ></video>
        @if ($caption)
            <figcaption class="mt-3 text-center text-sm font-medium text-slate-500 dark:text-slate-400">
                {{ $caption }}
            </figcaption>
        @endif
    </figure>
@else
    <div class="mb-8 md:mb-10 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center dark:border-slate-600 dark:bg-slate-900/40">
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('mksine::page_builder.no_video_selected') }}</p>
    </div>
@endif
