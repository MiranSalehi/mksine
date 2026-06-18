<div
    class="mksine-sidebar-nav-search-ctn"
    x-data="mksineSidebarNavSearch()"
    x-show="$store.sidebar.isOpen"
    x-cloak
    @keydown.window.ctrl.k.prevent="$refs.searchInput?.focus()"
    @keydown.window.meta.k.prevent="$refs.searchInput?.focus()"
>
    <label class="sr-only" for="mksine-sidebar-nav-search">
        {{ __('mksine::common.sidebar_search_label') }}
    </label>

    <div class="mksine-sidebar-nav-search-field">
        <span class="mksine-sidebar-nav-search-icon" aria-hidden="true">
            {{ \Filament\Support\generate_icon_html(
                \Filament\Support\Icons\Heroicon::OutlinedMagnifyingGlass,
                attributes: (new \Illuminate\View\ComponentAttributeBag)->class(['size-4 shrink-0']),
                size: \Filament\Support\Enums\IconSize::Small,
            ) }}
        </span>

        <input
            id="mksine-sidebar-nav-search"
            type="search"
            x-ref="searchInput"
            x-model="query"
            x-on:input="apply()"
            x-on:keydown.escape.prevent="clear()"
            placeholder="{{ __('mksine::common.sidebar_search_placeholder') }}"
            autocomplete="off"
            spellcheck="false"
            class="mksine-sidebar-nav-search-input"
        >

        <button
            type="button"
            class="mksine-sidebar-nav-search-clear"
            x-show="query.length > 0"
            x-on:click="clear()"
            x-cloak
            title="{{ __('mksine::common.sidebar_search_clear') }}"
        >
            <span class="sr-only">{{ __('mksine::common.sidebar_search_clear') }}</span>
            {{ \Filament\Support\generate_icon_html(
                \Filament\Support\Icons\Heroicon::OutlinedXMark,
                attributes: (new \Illuminate\View\ComponentAttributeBag)->class(['size-4']),
                size: \Filament\Support\Enums\IconSize::Small,
            ) }}
        </button>
    </div>
</div>
