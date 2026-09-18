@php
    $kind = $kind ?? 'plugins';
    $isMarketplace = $isMarketplace ?? false;
    $isPlugins = $kind === 'plugins';
    $directoryUrl = $isPlugins
        ? \Miran\Mksine\Support\Marketplace::pluginsDirectoryUrl()
        : \Miran\Mksine\Support\Marketplace::themesDirectoryUrl();
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
    $searchQuery = trim((string) ($marketplaceSearch ?? ''));
    $listings = $listings ?? null;
    $snapshot = $marketplaceSnapshot ?? null;
    if ($listings === null && $isMarketplace && is_array($snapshot)) {
        $listings = \Miran\Mksine\Core\Marketplace\MarketplaceCatalogResult::fromSnapshot(
            $snapshot,
            $isPlugins
                ? \Miran\Mksine\Core\Marketplace\MarketplaceKind::Plugin
                : \Miran\Mksine\Core\Marketplace\MarketplaceKind::Theme,
        );
    }
    $installedIds = $installedIds ?? [];
    $installedVersions = $installedVersions ?? [];
    $updatableIds = $updatableIds ?? [];
    $canMarketplaceUpdate = $canMarketplaceUpdate ?? false;
    $emptyCopy = $searchQuery !== ''
        ? ($isPlugins ? __('mksine::marketplace.empty_search_plugins') : __('mksine::marketplace.empty_search_themes'))
        : ($isPlugins ? __('mksine::marketplace.empty_plugins') : __('mksine::marketplace.empty_themes'));
    $tabTargets = 'showInstalledCatalog,showMarketplaceCatalog,loadMarketplaceCatalog,retryMarketplaceCatalog,updatedMarketplaceSearch,goToMarketplacePage';
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
            wire:loading.attr="disabled"
            wire:target="{{ $tabTargets }}"
            aria-selected="{{ $isMarketplace ? 'false' : 'true' }}"
            @class([
                'relative inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-2 text-sm font-medium transition',
                'bg-white text-gray-900 shadow-sm ring-1 ring-black/5 dark:bg-gray-800 dark:text-white dark:ring-white/10' => ! $isMarketplace,
                'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => $isMarketplace,
            ])
        >
            <span class="relative inline-flex h-4 w-4 shrink-0 items-center justify-center">
                <span wire:loading.class="opacity-0" wire:target="showInstalledCatalog">
                    <x-heroicon-o-check-circle class="h-4 w-4" />
                </span>
                <span
                    class="pointer-events-none absolute inset-0 hidden items-center justify-center"
                    wire:loading.flex
                    wire:target="showInstalledCatalog"
                >
                    <x-filament::loading-indicator class="h-4 w-4 shrink-0" />
                </span>
            </span>
            {{ __('mksine::marketplace.tab_installed') }}
        </button>
        <button
            type="button"
            role="tab"
            wire:click="showMarketplaceCatalog"
            wire:loading.attr="disabled"
            wire:target="{{ $tabTargets }}"
            aria-selected="{{ $isMarketplace ? 'true' : 'false' }}"
            @class([
                'relative inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-lg px-3.5 py-2 text-sm font-medium transition',
                'bg-white text-gray-900 shadow-sm ring-1 ring-black/5 dark:bg-gray-800 dark:text-white dark:ring-white/10' => $isMarketplace,
                'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' => ! $isMarketplace,
            ])
        >
            <span class="relative inline-flex h-4 w-4 shrink-0 items-center justify-center">
                <span wire:loading.class="opacity-0" wire:target="showMarketplaceCatalog,loadMarketplaceCatalog">
                    <x-heroicon-o-squares-plus class="h-4 w-4" />
                </span>
                <span
                    class="pointer-events-none absolute inset-0 hidden items-center justify-center"
                    wire:loading.flex
                    wire:target="showMarketplaceCatalog,loadMarketplaceCatalog"
                >
                    <x-filament::loading-indicator class="h-4 w-4 shrink-0" />
                </span>
            </span>
            {{ __('mksine::marketplace.tab_add') }}
        </button>
    </div>

    @if ($isMarketplace)
        <div class="space-y-5" wire:key="mksine-marketplace-{{ $kind }}">
            <div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    {{ $title }}
                </h3>
                <p class="mt-2 max-w-2xl text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                    {{ $body }}
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <x-filament::button
                        tag="a"
                        :href="$directoryUrl"
                        target="_blank"
                        rel="noopener noreferrer"
                        color="gray"
                        outlined
                        icon="heroicon-o-arrow-top-right-on-square"
                        size="sm"
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
                        size="sm"
                        class="whitespace-nowrap"
                    >
                        {{ __('mksine::marketplace.visit_site', ['host' => $hostLabel]) }}
                    </x-filament::button>
                </div>
            </div>

            <div class="relative">
                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute start-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400 dark:text-gray-500" />
                <input
                    type="search"
                    wire:model.live.debounce.400ms="marketplaceSearch"
                    placeholder="{{ $searchPlaceholder }}"
                    aria-label="{{ $searchPlaceholder }}"
                    class="w-full rounded-xl border border-gray-200/80 bg-white py-2.5 ps-10 pe-3 text-sm text-gray-900 shadow-sm ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-100 dark:ring-white/5"
                >
            </div>

            @if (! $listings instanceof \Miran\Mksine\Core\Marketplace\MarketplaceCatalogResult)
                <div
                    wire:init="loadMarketplaceCatalog"
                    x-init="\$wire.loadMarketplaceCatalog()"
                    wire:key="mks-mkt-load-{{ $kind }}-{{ $searchQuery }}-{{ $marketplacePage ?? 1 }}"
                    class="min-h-[18rem]"
                    aria-busy="true"
                    aria-live="polite"
                >
                    <p class="sr-only">{{ __('mksine::marketplace.loading') }}</p>
                    @if ($isPlugins)
                        <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-900/20 dark:ring-white/5">
                            @foreach (range(1, 4) as $slot)
                                <div class="flex h-[4.5rem] items-center gap-3 border-b border-gray-100 px-5 last:border-b-0 dark:border-gray-800">
                                    <div class="h-11 w-11 shrink-0 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-800"></div>
                                    <div class="min-w-0 flex-1 space-y-2">
                                        <div class="h-3 w-40 max-w-full animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                                        <div class="h-2.5 w-64 max-w-full animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                                    </div>
                                    <div class="hidden h-8 w-20 shrink-0 animate-pulse rounded-lg bg-gray-100 sm:block dark:bg-gray-800"></div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach (range(1, 6) as $slot)
                                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-900/20 dark:ring-white/5">
                                    <div class="aspect-video animate-pulse bg-gray-100 dark:bg-gray-800"></div>
                                    <div class="space-y-2 p-4">
                                        <div class="h-3.5 w-32 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                                        <div class="h-2.5 w-24 animate-pulse rounded bg-gray-100 dark:bg-gray-800"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @elseif (! $listings->ok)
                <div class="flex min-h-[18rem] flex-col items-center justify-center gap-4 rounded-2xl border border-amber-200/80 bg-amber-50 px-5 py-4 text-center text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
                    <p>{{ $listings->error ?: __('mksine::marketplace.unreachable') }}</p>
                    <x-filament::button
                        size="sm"
                        color="warning"
                        icon="heroicon-o-arrow-path"
                        wire:click="retryMarketplaceCatalog"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center justify-center whitespace-nowrap"
                    >
                        {{ __('mksine::marketplace.retry') }}
                    </x-filament::button>
                </div>
            @elseif ($listings->items === [])
                <div class="flex min-h-[18rem] flex-col items-center justify-center rounded-2xl border border-gray-200/80 bg-white px-5 py-10 text-center shadow-sm dark:border-gray-700 dark:bg-gray-900/40">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $emptyCopy }}
                    </p>
                </div>
            @elseif ($isPlugins)
                <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-900/20 dark:ring-white/5">
                    @foreach ($listings->items as $listing)
                        @php
                            $localVersion = $installedVersions[$listing->packageId] ?? null;
                            $hasUpdate = is_string($localVersion) && \Miran\Mksine\Core\Marketplace\MarketplaceRelease::isNewer($listing->version, $localVersion);
                        @endphp
                        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 last:border-b-0 sm:flex-row sm:items-center sm:gap-4 dark:border-gray-800">
                            @if ($listing->imageUrl)
                                <img
                                    src="{{ $listing->imageUrl }}"
                                    alt="{{ $listing->name }}"
                                    class="h-11 w-11 shrink-0 rounded-xl object-cover ring-1 ring-inset ring-black/5 dark:ring-white/10"
                                />
                            @else
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800">
                                <x-heroicon-o-puzzle-piece class="h-5 w-5 text-gray-600 dark:text-gray-300" />
                            </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $listing->name }}</p>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    v{{ $listing->version }}
                                    @if ($listing->authorName)
                                        · {{ $listing->authorName }}
                                    @endif
                                    @if ($listing->license !== '')
                                        · {{ $listing->license }}
                                    @endif
                                </p>
                                @if ($hasUpdate && is_string($localVersion))
                                    <p class="mt-0.5 text-xs font-medium text-amber-700 dark:text-amber-300">
                                        {{ __('mksine::marketplace.version_upgrade', ['from' => $localVersion, 'to' => $listing->version]) }}
                                    </p>
                                @endif
                                @if ($listing->summary !== '')
                                    <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-gray-600 dark:text-gray-400">{{ $listing->summary }}</p>
                                @endif
                                @if ($listing->changelog !== '')
                                    <p class="mt-1 line-clamp-2 text-xs leading-relaxed text-gray-500 dark:text-gray-500">{{ $listing->changelog }}</p>
                                @endif
                            </div>
                            @include('mksine::filament.partials.marketplace-listing-actions', [
                                'listing' => $listing,
                                'installedVersions' => $installedVersions,
                                'updatableIds' => $updatableIds,
                                'canMarketplaceUpdate' => $canMarketplaceUpdate,
                                'stack' => false,
                            ])
                        </div>
                    @endforeach
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($listings->items as $listing)
                        @php
                            $localVersion = $installedVersions[$listing->packageId] ?? null;
                            $hasUpdate = is_string($localVersion) && \Miran\Mksine\Core\Marketplace\MarketplaceRelease::isNewer($listing->version, $localVersion);
                        @endphp
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-900/20 dark:ring-white/5">
                            @if ($listing->imageUrl)
                                <img
                                    src="{{ $listing->imageUrl }}"
                                    alt="{{ $listing->name }}"
                                    class="aspect-video w-full object-cover"
                                />
                            @else
                            <div class="aspect-video bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-800 dark:to-gray-900"></div>
                            @endif
                            <div class="space-y-3 p-4">
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $listing->name }}</h4>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                        v{{ $listing->version }}
                                        @if ($listing->authorName)
                                            · {{ $listing->authorName }}
                                        @endif
                                        @if ($listing->license !== '')
                                            · {{ $listing->license }}
                                        @endif
                                    </p>
                                </div>
                                @if ($hasUpdate && is_string($localVersion))
                                    <p class="text-xs font-medium text-amber-700 dark:text-amber-300">
                                        {{ __('mksine::marketplace.version_upgrade', ['from' => $localVersion, 'to' => $listing->version]) }}
                                    </p>
                                @endif
                                @if ($listing->summary !== '')
                                    <p class="line-clamp-2 text-xs leading-relaxed text-gray-600 dark:text-gray-400">{{ $listing->summary }}</p>
                                @endif
                                @if ($listing->changelog !== '')
                                    <p class="line-clamp-2 text-xs leading-relaxed text-gray-500 dark:text-gray-500">{{ $listing->changelog }}</p>
                                @endif
                                @include('mksine::filament.partials.marketplace-listing-actions', [
                                    'listing' => $listing,
                                    'installedVersions' => $installedVersions,
                                    'updatableIds' => $updatableIds,
                                    'canMarketplaceUpdate' => $canMarketplaceUpdate,
                                    'stack' => true,
                                ])
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($listings instanceof \Miran\Mksine\Core\Marketplace\MarketplaceCatalogResult && $listings->ok && ($listings->nextPage || $listings->prevPage))
                <div class="flex items-center justify-between gap-3">
                    <x-filament::button
                        color="gray"
                        outlined
                        size="sm"
                        wire:click="goToMarketplacePage({{ $listings->prevPage ?? 1 }})"
                        :disabled="! $listings->prevPage"
                        class="whitespace-nowrap"
                    >
                        {{ __('mksine::marketplace.prev_page') }}
                    </x-filament::button>
                    <x-filament::button
                        color="gray"
                        outlined
                        size="sm"
                        wire:click="goToMarketplacePage({{ $listings->nextPage ?? $listings->currentPage }})"
                        :disabled="! $listings->nextPage"
                        class="whitespace-nowrap"
                    >
                        {{ __('mksine::marketplace.next_page') }}
                    </x-filament::button>
                </div>
            @endif
        </div>
    @endif
</div>
