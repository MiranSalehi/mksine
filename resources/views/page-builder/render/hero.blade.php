@php
    $title = $data['title'] ?? '';
    $subtitle = $data['subtitle'] ?? '';
    $backgroundImageId = $data['background_image'] ?? null;
    $overlay = $data['overlay'] ?? 'dark';
    $height = $data['height'] ?? 'medium';
    $textAlignment = $data['text_alignment'] ?? 'center';
    $textColor = $data['text_color'] ?? 'white';
    $buttonText = $data['button_text'] ?? '';
    $buttonUrl = \Miran\Mksine\Core\PageBuilder\BuilderUrl::href($data['button_url'] ?? '');
    $buttonStyle = $data['button_style'] ?? 'primary';
    $showSecondary = $data['show_secondary_button'] ?? false;
    $secondaryText = $data['secondary_button_text'] ?? '';
    $secondaryUrl = \Miran\Mksine\Core\PageBuilder\BuilderUrl::href($data['secondary_button_url'] ?? '');

    // Get background image URL
    $backgroundUrl = null;
    if ($backgroundImageId) {
        $media = app(\Miran\Mksine\Core\PageBuilder\BuilderMediaIndex::class)->find($backgroundImageId);
        $backgroundUrl = $media?->url;
    }

    $heightClass = match($height) {
        'small' => 'min-h-[300px]',
        'medium' => 'min-h-[450px]',
        'large' => 'min-h-[600px]',
        'full' => 'min-h-screen',
        default => 'min-h-[450px]',
    };

    $alignmentClass = match($textAlignment) {
        'left' => 'text-start items-start',
        'right' => 'text-end items-end',
        default => 'text-center items-center',
    };

    $textColorClass = $textColor === 'dark' ? 'text-gray-900 dark:text-white' : 'text-white';

    $overlayClass = match($overlay) {
        'light' => 'bg-white/50 dark:bg-black/30',
        'dark' => 'bg-black/50',
        'gradient' => 'bg-gradient-to-b from-black/70 via-black/50 to-black/70',
        default => '',
    };

    $surfaceClass = $backgroundUrl
        ? ''
        : ($textColor === 'dark' ? 'bg-zinc-50 dark:bg-zinc-950' : 'bg-zinc-900');

    $buttonClasses = match($buttonStyle) {
        'secondary' => 'bg-white text-zinc-900 hover:bg-zinc-100',
        'outline' => 'bg-transparent border-2 border-white text-white hover:bg-white hover:text-zinc-900',
        default => 'bg-white text-zinc-900 hover:bg-zinc-100',
    };
@endphp

<section
    {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('frame', (bool) ($preview ?? false)) !!}
    class="relative {{ $heightClass }} {{ $surfaceClass }} flex items-center justify-center overflow-hidden"
    @if($backgroundUrl) style="background-image: url('{{ asset($backgroundUrl) }}'); background-size: cover; background-position: center;" @endif
>
    {{-- Overlay --}}
    @if($overlay !== 'none')
        <div class="absolute inset-0 {{ $overlayClass }}"></div>
    @endif

    {{-- Content --}}
    <div class="relative z-10 container mx-auto px-4 flex flex-col {{ $alignmentClass }} max-w-4xl">
        @if($title)
            <h1 {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('title', (bool) ($preview ?? false)) !!} class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-bold {{ $textColorClass }} mb-4 leading-tight">
                {{ $title }}
            </h1>
        @endif

        @if($subtitle)
            <p {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('subtitle', (bool) ($preview ?? false)) !!} class="text-lg sm:text-xl md:text-2xl {{ $textColorClass }} opacity-90 mb-8 max-w-2xl">
                {{ $subtitle }}
            </p>
        @endif

        @if($buttonText || ($showSecondary && $secondaryText))
            <div class="flex flex-wrap gap-4 {{ $textAlignment === 'center' ? 'justify-center' : ($textAlignment === 'right' ? 'justify-end' : 'justify-start') }}">
                @if($buttonText && $buttonUrl)
                    <a {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('button', (bool) ($preview ?? false)) !!} href="{{ $buttonUrl }}" class="inline-flex items-center px-6 py-3 sm:px-8 sm:py-4 font-semibold rounded-lg transition-all shadow-lg hover:shadow-xl {{ $buttonClasses }}">
                        {{ $buttonText }}
                    </a>
                @endif

                @if($showSecondary && $secondaryText && $secondaryUrl)
                    <a {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('secondary', (bool) ($preview ?? false)) !!} href="{{ $secondaryUrl }}" class="inline-flex items-center px-6 py-3 sm:px-8 sm:py-4 font-semibold rounded-lg transition-all bg-transparent border-2 border-white/80 {{ $textColorClass }} hover:bg-white/10 dark:border-gray-400 dark:hover:bg-white/10">
                        {{ $secondaryText }}
                    </a>
                @endif
            </div>
        @endif
    </div>
</section>
