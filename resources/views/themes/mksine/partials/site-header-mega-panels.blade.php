@php
    $headerTree = app(\Miran\Mksine\Services\MenuService::class)->forLocation('header_primary');
    $topItems = $headerTree['items'] ?? [];
    $megaRoots = array_values(array_filter($topItems, fn (array $i): bool => ! empty($i['children'])));
@endphp
@if (! empty($megaRoots))
    <div
        id="site-header-mega-panels"
        class="site-header-mega-panels"
        aria-hidden="true"
    >
        <div class="site-header-mega-panels__surface max-h-[min(75vh,52rem)] overflow-y-auto rounded-xl border border-gray-200 bg-white px-6 py-8 shadow-2xl ring-1 ring-black/10 dark:border-gray-700 dark:bg-gray-900 dark:ring-white/10 sm:px-8 xl:px-10">
            @foreach ($megaRoots as $item)
                @include('mksine::themes.mksine.partials.site-header-mega-panel-root', ['item' => $item])
            @endforeach
        </div>
    </div>
@endif
