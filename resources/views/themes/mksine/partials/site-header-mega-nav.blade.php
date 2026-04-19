@php
    $headerTree = app(\Miran\Mksine\Services\MenuService::class)->forLocation('header_primary');
    $topItems = $headerTree['items'] ?? [];
@endphp
@if (! empty($topItems))
    <nav
        id="site-header-mega-nav"
        class="hidden min-w-0 items-center gap-0.5 whitespace-nowrap lg:flex xl:gap-1"
        aria-label="{{ __('Primary') }}"
    >
        @foreach ($topItems as $item)
            @if (! empty($item['children']))
                <button
                    type="button"
                    class="site-header-mega-trigger group inline-flex items-center gap-0.5 rounded-full py-2 pr-1 pl-3 text-sm font-medium text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                    data-mega-nav-trigger
                    data-toggle-submenu="mega-{{ $item['id'] }}"
                    aria-expanded="false"
                    aria-haspopup="true"
                    aria-controls="site-header-mega-panels"
                >
                    {{ $item['label'] }}
                    <svg class="size-6 shrink-0 text-gray-300 transition group-aria-expanded:rotate-180 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke="currentColor" stroke-width="1.5" d="m7 9.5 5 5 5-5"/>
                    </svg>
                </button>
            @else
                <a
                    href="{{ $item['url'] ?: '#' }}"
                    @if (! empty($item['target'])) target="{{ $item['target'] }}" @endif
                    @if (($item['target'] ?? '_self') === '_blank') rel="noopener noreferrer" @endif
                    class="inline-flex items-center rounded-full py-2 px-3 text-sm font-medium text-gray-600 transition-colors hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                >
                    {{ $item['label'] }}
                </a>
            @endif
        @endforeach
    </nav>
@endif
