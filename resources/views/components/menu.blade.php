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
            <li>
                <a href="{{ $item['url'] }}" 
                   @if(!empty($item['target'])) target="{{ $item['target'] }}" @endif
                >
                    {{ $item['label'] }}
                </a>

                @if(!empty($item['children']))
                    <x-mksine::menu :items="$item['children']" :location="$location" :depth="$depth + 1" />
                @endif
            </li>
        @endforeach
    </ul>
@endif
