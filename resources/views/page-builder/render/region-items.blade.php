@php
    $items = is_array($items ?? null) ? array_values($items) : [];
    $preview = (bool) ($preview ?? false);
    $canvas = $preview && (bool) ($canvas ?? false);
    $visited = is_array($visited ?? null) ? $visited : [];
    $parentId = is_string($parentId ?? null) ? $parentId : '';
    $columnIndex = is_numeric($columnIndex ?? null) ? (int) $columnIndex : null;
    $placeInserts = $canvas && $parentId !== '' && $columnIndex !== null;
@endphp
@if ($placeInserts)
<div data-mksine-drop data-mksine-parent="{{ $parentId }}" data-mksine-column="{{ $columnIndex }}">
@endif
@if ($placeInserts)
    @include('mksine::page-builder.render.canvas-insert', [
        'position' => 0,
        'parentId' => $parentId,
        'columnIndex' => $columnIndex,
        'visible' => $items === [],
    ])
@endif
@foreach ($items as $index => $item)
    @if ($placeInserts && $index > 0)
        @include('mksine::page-builder.render.canvas-insert', [
            'position' => $index,
            'parentId' => $parentId,
            'columnIndex' => $columnIndex,
        ])
    @endif
    @include('mksine::page-builder.render.block', [
        'block' => $item,
        'preview' => $preview,
        'canvas' => $canvas,
        'visited' => $visited,
        'nestParent' => $parentId,
        'nestColumn' => $columnIndex,
    ])
@endforeach
@if ($placeInserts && $items !== [])
    @include('mksine::page-builder.render.canvas-insert', [
        'position' => count($items),
        'parentId' => $parentId,
        'columnIndex' => $columnIndex,
    ])
@endif
@if ($placeInserts)
</div>
@endif
