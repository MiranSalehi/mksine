@php
    /** @var \Miran\Mksine\Core\Marketplace\MarketplacePackage $listing */
    $installedVersions = $installedVersions ?? [];
    $installed = array_key_exists($listing->packageId, $installedVersions);
    $localVersion = $installed ? (string) $installedVersions[$listing->packageId] : null;
    $hasUpdate = $installed && is_string($localVersion) && \Miran\Mksine\Core\Marketplace\MarketplaceRelease::isNewer($listing->version, $localVersion);
    $canMarketplaceUpdate = $canMarketplaceUpdate ?? false;
    $updatableIds = $updatableIds ?? [];
    $showUpdate = $hasUpdate && $canMarketplaceUpdate && in_array($listing->packageId, $updatableIds, true);
    $stack = $stack ?? false;
    $appearance = $appearance ?? 'buttons';
    $isDirectory = $appearance === 'directory';
    $directoryBase = 'inline-flex flex-1 items-center justify-center rounded-md px-3 py-1.5 text-center text-xs font-semibold shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 disabled:cursor-not-allowed disabled:opacity-50';
    $outlineClass = $directoryBase.' is-outline border border-gray-300 bg-white font-medium text-gray-800 hover:border-gray-400 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-800';
    $installClass = $directoryBase.' is-install border border-transparent bg-primary-600 text-white hover:bg-primary-500';
    $updateClass = $directoryBase.' is-update border border-transparent bg-amber-500 text-white hover:bg-amber-400 focus-visible:ring-amber-500';
    $installedChipClass = $directoryBase.' is-installed cursor-default gap-1.5 border border-emerald-200 bg-emerald-100 font-semibold text-emerald-800 shadow-none dark:border-emerald-500/30 dark:bg-emerald-500/20 dark:text-emerald-200';
@endphp

@if ($isDirectory)
    <ul class="mksine-marketplace-directory-actions flex list-none flex-row items-stretch gap-1.5 p-0">
        <li>
            @if ($showUpdate)
                <button
                    type="button"
                    wire:click="updateFromMarketplace('{{ $listing->slug }}')"
                    wire:loading.attr="disabled"
                    class="{{ $updateClass }}"
                >
                    {{ __('mksine::marketplace.update') }}
                </button>
            @elseif ($installed)
                <span class="{{ $installedChipClass }}" role="status">
                    <x-heroicon-s-check class="h-3.5 w-3.5" />
                    {{ __('mksine::marketplace.installed_badge') }}
                </span>
            @else
                <button
                    type="button"
                    wire:click="installFromMarketplace('{{ $listing->slug }}')"
                    wire:loading.attr="disabled"
                    class="{{ $installClass }}"
                >
                    {{ __('mksine::marketplace.install_now') }}
                </button>
            @endif
        </li>
        @if ($listing->url !== '')
            <li>
                <a
                    href="{{ $listing->url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="{{ $outlineClass }}"
                >
                    {{ __('mksine::marketplace.more_details') }}
                </a>
            </li>
        @endif
    </ul>
@else
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
            <span class="inline-flex h-8 items-center justify-center gap-1.5 rounded-full bg-emerald-100 px-2.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-200" role="status">
                <x-heroicon-s-check class="h-3.5 w-3.5" />
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
@endif
