@php
    $type = $block['type'] ?? 'unknown';
    $data = $block['data'] ?? [];
    $children = $block['children'] ?? null;
    $presentation = \Miran\Mksine\Core\PageBuilder\BlockPresentation::normalize(
        is_array($block['presentation'] ?? null) ? $block['presentation'] : [],
    );
    $anchor = $presentation['anchor'];
    $wrapperClass = \Miran\Mksine\Core\PageBuilder\BlockPresentation::wrapperClass($presentation);
    $wrapperStyle = \Miran\Mksine\Core\PageBuilder\BlockPresentation::wrapperStyle($presentation);
    $preview = (bool) ($preview ?? false);
    $canvas = $preview && (bool) ($canvas ?? false);
    $visited = is_array($visited ?? null) ? $visited : [];

    $registry = app(\Miran\Mksine\Core\PageBuilder\ComponentRegistry::class);
    $viewName = $registry->resolveRenderView($type);
    $canvasName = '';
    if ($canvas) {
        $componentClass = $registry->get((string) $type);
        $canvasName = $componentClass ? $componentClass::getName() : (string) $type;
    }

    $nestParent = is_string($nestParent ?? null) ? $nestParent : '';
    $nestColumn = is_numeric($nestColumn ?? null) ? (int) $nestColumn : null;

    $includeData = [
        'data' => $data,
        'blockId' => $block['id'] ?? null,
        'preview' => $preview,
        'canvas' => $canvas,
        'visited' => $visited,
    ];
    if (array_key_exists('children', $block)) {
        $includeData['children'] = $children;
    }
@endphp
@once
    {!! \Miran\Mksine\Core\PageBuilder\BlockStyles::renderTag() !!}
@endonce
{{-- Tailwind safelist: mb-4 mb-8 mb-16 hidden md:block md:hidden p-4 p-8 p-16 bg-zinc-100 bg-amber-50 text-center text-end mx-auto w-full max-w-4xl --}}
<div
    @if ($anchor !== '') id="{{ $anchor }}" @endif
    @class(['mksine-block', $wrapperClass => $wrapperClass !== ''])
    @if ($wrapperStyle !== '') style="{{ $wrapperStyle }}" @endif
    @if (in_array($presentation['align'], ['left', 'center', 'right', 'end'], true)) data-mksine-align="{{ $presentation['align'] }}" @endif
    @if ($presentation['text_color'] !== '') data-mksine-text @endif
    @if ($presentation['background_color'] !== '' || $presentation['background'] !== 'none') data-mksine-bg @endif
    @if ($preview && is_string($block['id'] ?? null) && $block['id'] !== '') data-mksine-block-id="{{ $block['id'] }}" @endif
    @if ($nestParent !== '' && $nestColumn !== null) data-mksine-parent-id="{{ $nestParent }}" data-mksine-column-index="{{ $nestColumn }}" @endif
>
@if ($canvas && is_string($block['id'] ?? null) && $block['id'] !== '')
    <div class="mksine-canvas-tools" data-mksine-canvas-tools>
        <span class="mksine-canvas-name" data-mksine-drag>{{ $canvasName }}</span>
        <button type="button" data-mksine-action="settings" aria-label="{{ __('mksine::page_builder.edit') }}" title="{{ __('mksine::page_builder.edit') }}">✎</button>
        <button type="button" data-mksine-action="move-up" aria-label="{{ __('mksine::page_builder.move_up') }}" title="{{ __('mksine::page_builder.move_up') }}">↑</button>
        <button type="button" data-mksine-action="move-down" aria-label="{{ __('mksine::page_builder.move_down') }}" title="{{ __('mksine::page_builder.move_down') }}">↓</button>
        <button type="button" data-mksine-action="duplicate" aria-label="{{ __('mksine::page_builder.duplicate') }}" title="{{ __('mksine::page_builder.duplicate') }}">⧉</button>
        <button type="button" data-mksine-action="remove" aria-label="{{ __('mksine::page_builder.delete') }}" title="{{ __('mksine::page_builder.delete') }}">×</button>
    </div>
@endif
@if ($viewName !== '' && \Illuminate\Support\Facades\View::exists($viewName))
    @include($viewName, $includeData)
@else
    {{-- Unknown component or missing view --}}
    <div
        class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg text-yellow-800 dark:text-yellow-200 text-sm">
        {{ __('mksine::page_builder.unknown_component_type') }} {{ $type }}
    </div>
@endif
</div>