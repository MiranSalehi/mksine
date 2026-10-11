@php
  $data = is_array($data ?? null) ? $data : [];
  $maxWidth = $data['max_width'] ?? 'full';
  $fullBleed = filter_var($data['background_full_bleed'] ?? false, FILTER_VALIDATE_BOOLEAN);

  $innerPadding = 'px-2 sm:px-4 md:px-6 lg:px-8';

  $widthClass = match ($maxWidth) {
    'prose' => 'max-w-prose w-full mx-auto',
    '3xl' => 'max-w-3xl w-full mx-auto',
    '5xl' => 'max-w-5xl w-full mx-auto',
    '6xl' => 'max-w-6xl w-full mx-auto',
    '7xl' => 'max-w-7xl w-full mx-auto',
    default => 'w-full max-w-none',
  };

  $items = [];
  if (isset($children[0]) && is_array($children[0]) && !empty($children[0]['items']) && is_array($children[0]['items'])) {
    $items = $children[0]['items'];
  }

  $gradient = \Miran\Mksine\Core\PageBuilder\Components\ContainerInsetComponent::normalizedBackgroundGradient($data['background_gradient'] ?? null);
  $bgHex = \Miran\Mksine\Core\PageBuilder\Components\ContainerInsetComponent::normalizedBackgroundColor($data['background_color'] ?? null);

  $bgStyleParts = [];
  if ($bgHex !== null) {
    $bgStyleParts[] = 'background-color: ' . $bgHex;
  }
  if ($gradient !== null) {
    $bgStyleParts[] = 'background-image: ' . $gradient;
  }
  $bgStyle = $bgStyleParts === [] ? null : implode('; ', $bgStyleParts) . ';';
@endphp
@if ($fullBleed && $bgStyle !== null)
  <div {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('frame', (bool) ($preview ?? false)) !!} class="w-full mksine-container-inset--bleed" style="{{ e($bgStyle) }}">
    <div class="mksine-container-inset {{ $innerPadding }} {{ $widthClass }}">
      @include('mksine::page-builder.render.region-items', [
        'items' => $items,
        'preview' => $preview ?? false,
        'canvas' => $canvas ?? false,
        'visited' => $visited ?? [],
        'parentId' => $blockId ?? null,
        'columnIndex' => 0,
      ])
    </div>
  </div>
@else
  <div {!! \Miran\Mksine\Core\PageBuilder\BlockRegion::attributes('frame', (bool) ($preview ?? false)) !!} class="mksine-container-inset {{ $innerPadding }} {{ $widthClass }}" @if ($bgStyle !== null)
  style="{{ e($bgStyle) }}" @endif>
    @include('mksine::page-builder.render.region-items', [
      'items' => $items,
      'preview' => $preview ?? false,
      'canvas' => $canvas ?? false,
      'visited' => $visited ?? [],
      'parentId' => $blockId ?? null,
      'columnIndex' => 0,
    ])
  </div>
@endif