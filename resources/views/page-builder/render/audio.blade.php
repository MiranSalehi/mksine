@php
    $audioId = $data['audio'] ?? null;
    $caption = $data['caption'] ?? '';
    $controls = (bool) ($data['controls'] ?? true);
    $autoplay = (bool) ($data['autoplay'] ?? false);
    $loop = (bool) ($data['loop'] ?? false);

    $media = $audioId ? \Miran\Mksine\Models\Media::query()->find($audioId) : null;
    $src = $media?->full_url;
@endphp

@if ($src && $media?->isAudio())
    <figure class="mb-8 md:mb-10">
        <audio
            class="w-full"
            src="{{ $src }}"
            @if($controls) controls @endif
            @if($autoplay) autoplay @endif
            @if($loop) loop @endif
            preload="metadata"
        ></audio>
        @if ($caption)
            <figcaption class="mt-3 text-center text-sm font-medium text-slate-500 dark:text-slate-400">
                {{ $caption }}
            </figcaption>
        @endif
    </figure>
@else
    <div class="mb-8 md:mb-10 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center dark:border-slate-600 dark:bg-slate-900/40">
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('mksine::page_builder.no_audio_selected') }}</p>
    </div>
@endif
