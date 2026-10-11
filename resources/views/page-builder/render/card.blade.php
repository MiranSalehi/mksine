@php
    $styles = \Miran\Mksine\Core\PageBuilder\Components\CardComponent::styles(is_array($data ?? null) ? $data : []);
    $buckets = is_array($children ?? null) ? array_values($children) : [];
    $buckets = array_slice($buckets, 0, 3);
    while (count($buckets) < 3) {
        $buckets[] = ['items' => []];
    }
    $items = static function (array $buckets, int $index): array {
        $items = $buckets[$index]['items'] ?? [];

        return is_array($items) ? $items : [];
    };
@endphp

<article {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('frame', (bool) ($preview ?? false)) !!} style="{{ e($styles['card']) }}">
    @if ($styles['show_header'])
        <header {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('header', (bool) ($preview ?? false)) !!} style="{{ e($styles['header']) }}">
            @include('mksine::page-builder.render.region-items', [
                'items' => $items($buckets, 0),
                'preview' => $preview ?? false,
                'canvas' => $canvas ?? false,
                'visited' => $visited ?? [],
                'parentId' => $blockId ?? null,
                'columnIndex' => 0,
            ])
        </header>
    @endif
    <div {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('body', (bool) ($preview ?? false)) !!} style="{{ e($styles['body']) }}">
        @include('mksine::page-builder.render.region-items', [
            'items' => $items($buckets, 1),
            'preview' => $preview ?? false,
            'canvas' => $canvas ?? false,
            'visited' => $visited ?? [],
            'parentId' => $blockId ?? null,
            'columnIndex' => 1,
        ])
    </div>
    @if ($styles['show_footer'])
        <footer {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('footer', (bool) ($preview ?? false)) !!} style="{{ e($styles['footer']) }}">
            @include('mksine::page-builder.render.region-items', [
                'items' => $items($buckets, 2),
                'preview' => $preview ?? false,
                'canvas' => $canvas ?? false,
                'visited' => $visited ?? [],
                'parentId' => $blockId ?? null,
                'columnIndex' => 2,
            ])
        </footer>
    @endif
</article>
