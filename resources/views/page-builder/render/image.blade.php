@php
    $imageId = $data['image'] ?? null;
    $alt = $data['alt'] ?? '';
    $caption = $data['caption'] ?? '';
    $size = $data['size'] ?? 'large';
    $alignment = $data['alignment'] ?? 'center';
    $rounded = $data['rounded'] ?? false;
    $shadow = $data['shadow'] ?? false;
    
    // Get image URL from media
    $imageUrl = null;
    if ($imageId) {
        $media = \Miran\Mksine\Models\Media::find($imageId);
        $imageUrl = $media?->url;
    }
    
    $sizeClasses = match($size) {
        'small' => 'max-w-sm',
        'medium' => 'max-w-lg',
        'large' => 'max-w-2xl',
        'full' => 'w-full',
        default => 'max-w-2xl',
    };
    
    $alignmentClasses = match($alignment) {
        'left' => '',
        'center' => 'mx-auto',
        'right' => 'ms-auto',
        default => 'mx-auto',
    };
    
    $imgClasses = collect([
        $rounded ? 'rounded-lg' : '',
        $shadow ? 'shadow-lg' : '',
    ])->filter()->implode(' ');
@endphp

@if($imageUrl)
    <figure class="mb-6 {{ $sizeClasses }} {{ $alignmentClasses }}">
        <img src="{{ asset($imageUrl) }}" alt="{{ $alt }}" class="w-full h-auto {{ $imgClasses }}">
        @if($caption)
            <figcaption class="text-center text-sm text-gray-500 dark:text-gray-400 mt-2">{{ $caption }}</figcaption>
        @endif
    </figure>
@else
    <div class="mb-6 {{ $sizeClasses }} {{ $alignmentClasses }} bg-gray-100 dark:bg-gray-800 rounded-lg p-8 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto text-gray-400 mb-2">
            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
        </svg>
        <p class="text-gray-500 dark:text-gray-400 text-sm">{{ __('mksine::page_builder.no_image_selected') }}</p>
    </div>
@endif
