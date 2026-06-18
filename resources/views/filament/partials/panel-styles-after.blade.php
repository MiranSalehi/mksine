@php
    $mksineStylesPath = base_path('packages/mksine/resources/dist/mksine.css');
@endphp

@if (file_exists($mksineStylesPath))
    <link
        href="{{ \Filament\Support\Facades\FilamentAsset::getStyleHref('mksine-styles', 'miran/mksine') }}"
        rel="stylesheet"
        data-navigate-track
    />
@endif
