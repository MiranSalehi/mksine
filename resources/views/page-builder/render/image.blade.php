@php
    $imageId = $data['image'] ?? null;
    $alt = $data['alt'] ?? '';
    $caption = $data['caption'] ?? '';
    $size = $data['size'] ?? 'large';
    $alignment = $data['alignment'] ?? 'center';
    $rounded = $data['rounded'] ?? false;
    $shadow = $data['shadow'] ?? false;

    $imageUrl = null;
    if ($imageId) {
        $media = \Miran\Mksine\Models\Media::find($imageId);
        $imageUrl = $media?->url;
    }

    $sizeClasses = match ($size) {
        'small' => 'max-w-sm',
        'medium' => 'max-w-lg',
        'large' => 'max-w-2xl',
        'full' => 'w-full',
        default => 'max-w-2xl',
    };

    $alignmentClasses = match ($alignment) {
        'left' => '',
        'center' => 'mx-auto',
        'right' => 'ms-auto',
        default => 'mx-auto',
    };

    $radiusClass = $rounded ? 'rounded-2xl' : 'rounded-lg';
    $shadowClass = $shadow
        ? 'shadow-lg shadow-slate-900/15 dark:shadow-black/50'
        : 'shadow-sm shadow-slate-900/5 dark:shadow-black/25';
@endphp

@if ($imageUrl)
    <figure class="group mb-8 md:mb-10 {{ $sizeClasses }} {{ $alignmentClasses }}">
        <div class="overflow-hidden {{ $radiusClass }} ring-1 ring-slate-900/5 dark:ring-white/10 {{ $shadowClass }}">
            <img
                src="{{ asset($imageUrl) }}"
                alt="{{ $alt }}"
                class="h-auto w-full object-cover transition duration-500 ease-out group-hover:scale-[1.03]"
            >
        </div>
        @if ($caption)
            <figcaption class="mt-3 text-center text-sm font-medium text-slate-500 dark:text-slate-400">
                {{ $caption }}
            </figcaption>
        @endif
    </figure>
@else
    <div class="mb-8 md:mb-10 {{ $sizeClasses }} {{ $alignmentClasses }} rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center dark:border-slate-600 dark:bg-slate-900/40">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mx-auto mb-3 h-12 w-12 text-slate-400 dark:text-slate-500">
            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
        </svg>
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('mksine::page_builder.no_image_selected') }}</p>
    </div>
@endif
