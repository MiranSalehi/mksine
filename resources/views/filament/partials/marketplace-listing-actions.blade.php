@php
    /** @var \Miran\Mksine\Core\Marketplace\MarketplacePackage $listing */
    $installed = array_key_exists($listing->packageId, $installedVersions);
    $localVersion = $installed ? (string) $installedVersions[$listing->packageId] : null;
    $hasUpdate = $installed && is_string($localVersion) && \Miran\Mksine\Core\Marketplace\MarketplaceRelease::isNewer($listing->version, $localVersion);
    $showUpdate = $hasUpdate && $canMarketplaceUpdate && in_array($listing->packageId, $updatableIds, true);
    $stack = $stack ?? false;
@endphp

<div @class([
    'flex shrink-0 flex-wrap items-center gap-2',
    'w-full flex-col [&>*]:w-full' => $stack,
])>
    @if ($showUpdate)
        <x-filament::button
            size="sm"
            color="warning"
            icon="heroicon-o-arrow-path"
            wire:click="updateFromMarketplace('{{ $listing->slug }}')"
            wire:loading.attr="disabled"
            class="inline-flex items-center justify-center whitespace-nowrap"
        >
            {{ __('mksine::marketplace.update') }}
        </x-filament::button>
    @elseif ($installed)
        <span class="inline-flex h-8 items-center justify-center rounded-full bg-emerald-100 px-2.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200">
            {{ __('mksine::marketplace.installed_badge') }}
        </span>
    @else
        <x-filament::button
            size="sm"
            icon="heroicon-o-arrow-down-tray"
            wire:click="installFromMarketplace('{{ $listing->slug }}')"
            wire:loading.attr="disabled"
            class="inline-flex items-center justify-center whitespace-nowrap"
        >
            {{ __('mksine::marketplace.install') }}
        </x-filament::button>
    @endif

    @if ($listing->url !== '')
        <x-filament::button
            tag="a"
            :href="$listing->url"
            target="_blank"
            rel="noopener noreferrer"
            size="sm"
            color="gray"
            outlined
            icon="heroicon-o-arrow-top-right-on-square"
            class="inline-flex items-center justify-center whitespace-nowrap"
        >
            {{ __('mksine::marketplace.view_listing') }}
        </x-filament::button>
    @endif
</div>
