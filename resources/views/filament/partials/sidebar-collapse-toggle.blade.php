@php
    $isRtl = __('filament-panels::layout.direction') === 'rtl';
    $isCollapsible = filament()->isSidebarCollapsibleOnDesktop() || filament()->isSidebarFullyCollapsibleOnDesktop();
    $collapseIcon = $isRtl ? \Filament\Support\Icons\Heroicon::OutlinedChevronRight : \Filament\Support\Icons\Heroicon::OutlinedChevronLeft;
    $expandIcon = $isRtl ? \Filament\Support\Icons\Heroicon::OutlinedChevronLeft : \Filament\Support\Icons\Heroicon::OutlinedChevronRight;
@endphp

@if ($isCollapsible)
    <div class="mksine-sidebar-collapse-ctn mt-auto hidden shrink-0 lg:block">
        <button
            type="button"
            class="mksine-sidebar-collapse-btn"
            x-data="{}"
            x-on:click="$store.sidebar.isOpen ? $store.sidebar.close() : $store.sidebar.open()"
        >
            <span
                x-show="$store.sidebar.isOpen"
                x-cloak
                class="flex w-full items-center gap-2"
            >
                {{ \Filament\Support\generate_icon_html($collapseIcon, attributes: (new \Illuminate\View\ComponentAttributeBag)->class(['size-5 shrink-0']), size: \Filament\Support\Enums\IconSize::Medium) }}
                <span class="mksine-sidebar-collapse-label truncate">
                    {{ __('mksine::common.sidebar_collapse') }}
                </span>
            </span>

            <span
                x-show="! $store.sidebar.isOpen"
                x-cloak
                class="flex w-full items-center justify-center"
                title="{{ __('mksine::common.sidebar_expand') }}"
            >
                {{ \Filament\Support\generate_icon_html($expandIcon, attributes: (new \Illuminate\View\ComponentAttributeBag)->class(['size-5 shrink-0']), size: \Filament\Support\Enums\IconSize::Medium) }}
            </span>
        </button>
    </div>
@endif
