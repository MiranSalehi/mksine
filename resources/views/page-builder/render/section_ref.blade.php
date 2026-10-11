@php
    $sectionId = (int) ($data['section_id'] ?? 0);
    $visited = is_array($visited ?? null) ? $visited : [];
    $preview = (bool) ($preview ?? false);
    $resolved = app(\Miran\Mksine\Core\PageBuilder\SectionBlocks::class)->resolve($sectionId, $visited);
@endphp
<div {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('section', $preview) !!}>
@if ($resolved['status'] === 'ok')
    @include('mksine::page-builder.render.payload', [
        'payload' => $resolved['blocks'],
        'preview' => false,
        'visited' => array_merge($visited, [$sectionId]),
    ])
@elseif ($preview && $resolved['status'] === 'missing')
    <p class="rounded-md border border-dashed border-zinc-300 px-3 py-2 text-sm text-zinc-500">
        {{ __('mksine::page_builder.section_missing') }}
    </p>
@endif
</div>
