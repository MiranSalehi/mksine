@php
    /** @var array<string, mixed> $item */
    $groups = [];
    $loose = [];
    foreach ($item['children'] ?? [] as $child) {
        if (! empty($child['children'])) {
            $groups[] = $child;
        } else {
            $loose[] = $child;
        }
    }
@endphp
<div data-header-submenu="mega-{{ $item['id'] }}" class="mega-panel-section hidden text-sm">
    <div class="grid grid-cols-1 gap-10 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($groups as $group)
            <div class="space-y-4">
                <div class="text-xs font-semibold tracking-wide text-gray-400 uppercase dark:text-gray-500">
                    {{ $group['label'] }}
                </div>
                <div class="space-y-3">
                    @foreach ($group['children'] as $child)
                        @include('mksine::themes.mksine.partials.site-header-mega-nested-link', ['node' => $child])
                    @endforeach
                </div>
            </div>
        @endforeach
        @if (! empty($loose))
            <div class="space-y-4">
                <div class="text-xs font-semibold tracking-wide text-gray-400 uppercase dark:text-gray-500">
                    {{ __('mksine::frontend.header_mega.links') }}
                </div>
                <div class="space-y-3">
                    @foreach ($loose as $link)
                        @include('mksine::themes.mksine.partials.site-header-mega-nested-link', ['node' => $link])
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
