@php
    $kind = $kind ?? 'plugins';
    $isMarketplace = $isMarketplace ?? false;
    $isPlugins = $kind === 'plugins';
    $directoryUrl = \Miran\Mksine\Support\Marketplace::directoryUrl();
    $siteUrl = \Miran\Mksine\Support\Marketplace::siteUrl();
    $hostLabel = \Miran\Mksine\Support\Marketplace::hostLabel();
    $title = $isPlugins
        ? __('mksine::marketplace.title_plugins')
        : __('mksine::marketplace.title_themes');
    $body = $isPlugins
        ? __('mksine::marketplace.body_plugins', ['host' => $hostLabel])
        : __('mksine::marketplace.body_themes', ['host' => $hostLabel]);
    $searchPlaceholder = $isPlugins
        ? __('mksine::marketplace.search_placeholder_plugins')
        : __('mksine::marketplace.search_placeholder_themes');
@endphp

<div class="mksine-marketplace-catalog space-y-6">
    <div
        role="tablist"
        aria-label="{{ $title }}"
        class="inline-flex max-w-full flex-wrap rounded-xl border border-gray-200/80 bg-gray-50/90 p-1 shadow-sm ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-900/60 dark:ring-white/5"
    >
        <button
            type="button"
            role="tab"
            wire:click="showInstalledCatalog"
            aria-selected="{{ $isMarketplace ? 'false' : 'true' }}"
            @class([
                'inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-2 text-sm font-medium transition',
                'bg-white text-gray-900 shadow-sm ring-1 ring-black/5 dark:bg-gray-800 dark:text-white dark:ring-white/10' => ! $isMarketplace,
                'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => $isMarketplace,
            ])
        >
            <x-heroicon-o-check-circle class="h-4 w-4 shrink-0" />
            {{ __('mksine::marketplace.tab_installed') }}
        </button>
        <button
            type="button"
            role="tab"
            wire:click="showMarketplaceCatalog"
            aria-selected="{{ $isMarketplace ? 'true' : 'false' }}"
            @class([
                'inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-2 text-sm font-medium transition',
                'bg-white text-gray-900 shadow-sm ring-1 ring-black/5 dark:bg-gray-800 dark:text-white dark:ring-white/10' => $isMarketplace,
                'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => ! $isMarketplace,
            ])
        >
            <x-heroicon-o-squares-plus class="h-4 w-4 shrink-0" />
            {{ __('mksine::marketplace.tab_add') }}
        </button>
    </div>

    @if ($isMarketplace)
        <div class="space-y-5" wire:key="mksine-marketplace-{{ $kind }}">
            <div class="relative">
                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute start-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500" />
                <input
                    type="search"
                    disabled
                    placeholder="{{ $searchPlaceholder }}"
                    aria-label="{{ $searchPlaceholder }}"
                    class="w-full cursor-not-allowed rounded-xl border border-gray-200/80 bg-gray-50 py-2.5 ps-10 pe-3 text-sm text-gray-500 shadow-sm ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-400 dark:ring-white/5"
                >
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ __('mksine::marketplace.search_disabled_hint') }}
            </p>

            <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-900/40 dark:ring-white/5">
                <div class="flex flex-col gap-6 p-6 sm:flex-row sm:items-start sm:gap-8">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-500/20 to-fuchsia-500/10 ring-1 ring-primary-500/20 dark:from-primary-500/30 dark:to-fuchsia-600/20">
                        @if ($isPlugins)
                            <x-heroicon-o-puzzle-piece class="h-8 w-8 text-primary-600 dark:text-primary-400" />
                        @else
                            <x-heroicon-o-paint-brush class="h-8 w-8 text-primary-600 dark:text-primary-400" />
                        @endif
                    </div>
                    <div class="min-w-0 flex-1 space-y-3">
                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-500/20 dark:text-amber-200">
                            {{ __('mksine::marketplace.coming_soon_badge') }}
                        </span>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                {{ $title }}
                            </h3>
                            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                                {{ $body }}
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 pt-1">
                            <x-filament::button
                                tag="a"
                                :href="$directoryUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                icon="heroicon-o-arrow-top-right-on-square"
                                class="whitespace-nowrap"
                            >
                                {{ __('mksine::marketplace.visit_directory') }}
                            </x-filament::button>
                            <x-filament::button
                                tag="a"
                                :href="$siteUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                color="gray"
                                outlined
                                class="whitespace-nowrap"
                            >
                                {{ __('mksine::marketplace.visit_site', ['host' => $hostLabel]) }}
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </div>

            <div aria-hidden="true">
                @if ($isPlugins)
                    <div class="overflow-hidden rounded-2xl border border-dashed border-gray-200 bg-white/60 ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-900/20 dark:ring-white/5">
                        @foreach (range(1, 4) as $slot)
                            <div class="flex items-center gap-3 border-b border-dashed border-gray-200 px-5 py-4 last:border-b-0 dark:border-gray-800">
                                <div class="h-11 w-11 shrink-0 rounded-xl bg-gray-100 dark:bg-gray-800"></div>
                                <div class="min-w-0 flex-1 space-y-2">
                                    <div class="h-3 w-40 rounded bg-gray-100 dark:bg-gray-800"></div>
                                    <div class="h-2.5 w-64 max-w-full rounded bg-gray-100 dark:bg-gray-800"></div>
                                </div>
                                <div class="hidden h-8 w-20 rounded-lg bg-gray-100 sm:block dark:bg-gray-800"></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach (range(1, 6) as $slot)
                            <div class="overflow-hidden rounded-xl border border-dashed border-gray-200 bg-white/60 ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-900/20 dark:ring-white/5">
                                <div class="aspect-video bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-800 dark:to-gray-900"></div>
                                <div class="space-y-2 p-4">
                                    <div class="h-3.5 w-32 rounded bg-gray-100 dark:bg-gray-800"></div>
                                    <div class="h-2.5 w-24 rounded bg-gray-100 dark:bg-gray-800"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                <p class="sr-only">{{ __('mksine::marketplace.ghost_preview') }}</p>
            </div>
        </div>
    @endif
</div>
