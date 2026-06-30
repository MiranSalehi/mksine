@php
    use Filament\Support\Facades\FilamentAsset;

    $mksineStylesHref = null;

    try {
        $mksineStylesHref = FilamentAsset::getStyleHref('mksine-styles', 'miran/mksine');
    } catch (\LogicException) {
        // Stylesheet not registered (missing dist build or filament:assets not run).
    }
@endphp

@if (filled($mksineStylesHref))
    <link
        href="{{ $mksineStylesHref }}"
        rel="stylesheet"
        data-navigate-track
    />
@endif
