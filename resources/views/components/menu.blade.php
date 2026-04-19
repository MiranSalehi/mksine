@props(['location' => null, 'items' => null, 'depth' => 0])

@php
    $menuItems = $items;

    if ($location && $items === null) {
        $menuTree = app(\Miran\Mksine\Services\MenuService::class)->forLocation($location);
        $menuItems = $menuTree ? $menuTree['items'] : [];
    }

    $ulClass = $depth === 0 ? "mksine-menu-{$location}" : "mksine-submenu-{$location} mksine-submenu-level-{$depth}";
@endphp

@if(!empty($menuItems))
    @if($depth === 0)
        <ul {{ $attributes->merge(['class' => $ulClass]) }}>
    @else
        <ul class="{{ $ulClass }}">
    @endif
        @foreach($menuItems as $item)
            @php
                $hasChildren = ! empty($item['children']);
            @endphp
            <li>
                @if ($hasChildren)
                    <span
                        class="mksine-menu-parent-trigger"
                        role="button"
                        tabindex="0"
                        aria-haspopup="true"
                        aria-expanded="false"
                    >{{ $item['label'] }}</span>
                @else
                    <a
                        href="{{ $item['url'] }}"
                        @if (! empty($item['target'])) target="{{ $item['target'] }}" @endif
                    >{{ $item['label'] }}</a>
                @endif

                @if ($hasChildren)
                    <x-mksine::menu :items="$item['children']" :location="$location" :depth="$depth + 1" />
                @endif
            </li>
        @endforeach
    </ul>
@endif
