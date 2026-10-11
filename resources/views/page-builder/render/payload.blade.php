@php
    $payload = is_array($payload ?? null) ? $payload : [];
    $preview = (bool) ($preview ?? false);
    $canvas = $preview && (bool) ($canvas ?? false);
    $visited = is_array($visited ?? null) ? $visited : [];
    app(\Miran\Mksine\Core\PageBuilder\BuilderMediaIndex::class)->warm($payload);
@endphp
@if ($canvas && $payload === [])
    @include('mksine::page-builder.render.canvas-empty')
@endif
@if ($canvas && $payload !== [])
    <div class="mksine-canvas-root" data-mksine-drop>
@endif
@foreach ($payload as $index => $block)
    @if ($canvas)
        @include('mksine::page-builder.render.canvas-insert', ['position' => $index])
    @endif
    @include('mksine::page-builder.render.block', [
        'block' => $block,
        'preview' => $preview,
        'canvas' => $canvas,
        'visited' => $visited,
    ])
@endforeach
@if ($canvas && $payload !== [])
    @include('mksine::page-builder.render.canvas-insert', ['position' => count($payload)])
    </div>
@endif
