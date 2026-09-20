@php
    /** @var \Miran\Mksine\Core\Marketplace\MarketplacePackage $listing */
    $installedVersions = $installedVersions ?? [];
    $installed = array_key_exists($listing->packageId, $installedVersions);
    $localVersion = $installed ? (string) $installedVersions[$listing->packageId] : null;
    $hasUpdate = $installed && is_string($localVersion) && $localVersion !== ''
        && \Miran\Mksine\Core\Marketplace\MarketplaceRelease::isNewer($listing->version, $localVersion);
    $publishedLabel = $listing->lastPublishedForHumans();
    $downloadLabel = $listing->formattedDownloadCount();
    $state = $hasUpdate ? 'update' : ($installed ? 'installed' : 'available');
@endphp

<article
    data-marketplace-state="{{ $state }}"
    @class([
        'mksine-marketplace-plugin-card flex h-full flex-col overflow-hidden rounded-xl border',
        'is-available border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900/30' => $state === 'available',
        'is-installed border-emerald-300 bg-emerald-50/70 dark:border-emerald-500/40 dark:bg-emerald-500/10' => $state === 'installed',
        'is-update border-amber-300 bg-amber-50/80 dark:border-amber-500/40 dark:bg-amber-500/10' => $state === 'update',
    ])
>
    <div class="flex flex-1 flex-col gap-4 p-5 sm:flex-row sm:items-start">
        <div class="flex min-w-0 flex-1 items-start gap-4">
            <div class="relative shrink-0">
                @if ($listing->imageUrl)
                    <img
                        src="{{ $listing->imageUrl }}"
                        alt="{{ $listing->name }}"
                        class="h-[72px] w-[72px] rounded-lg object-cover ring-1 ring-inset ring-black/5 dark:ring-white/10"
                    />
                @else
                    <div @class([
                        'flex h-[72px] w-[72px] items-center justify-center rounded-lg ring-1 ring-inset ring-black/5 dark:ring-white/10',
                        'bg-gray-100 dark:bg-gray-800' => $state === 'available',
                        'bg-emerald-100 dark:bg-emerald-500/20' => $state === 'installed',
                        'bg-amber-100 dark:bg-amber-500/20' => $state === 'update',
                    ])>
                        <x-heroicon-o-puzzle-piece @class([
                            'h-8 w-8',
                            'text-gray-500 dark:text-gray-300' => $state === 'available',
                            'text-emerald-700 dark:text-emerald-200' => $state === 'installed',
                            'text-amber-800 dark:text-amber-200' => $state === 'update',
                        ]) />
                    </div>
                @endif
                @if ($installed)
                    <span @class([
                        'absolute -end-1.5 -top-1.5 flex h-5 w-5 items-center justify-center rounded-full text-white ring-2 ring-white dark:ring-gray-950',
                        'bg-emerald-600' => ! $hasUpdate,
                        'bg-amber-500' => $hasUpdate,
                    ])>
                        @if ($hasUpdate)
                            <x-heroicon-s-arrow-path class="h-3 w-3" />
                        @else
                            <x-heroicon-s-check class="h-3 w-3" />
                        @endif
                    </span>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <h4 class="text-base font-semibold leading-snug text-gray-900 dark:text-white">
                    @if ($listing->url !== '')
                        <a
                            href="{{ $listing->url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="hover:text-primary-600 dark:hover:text-primary-400"
                        >
                            {{ $listing->name }}
                        </a>
                    @else
                        {{ $listing->name }}
                    @endif
                </h4>

                @if ($listing->summary !== '')
                    <p class="mt-1.5 line-clamp-3 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                        {{ $listing->summary }}
                    </p>
                @endif

                @if ($hasUpdate && is_string($localVersion))
                    <p class="mt-1.5 text-xs font-medium text-amber-700 dark:text-amber-300">
                        {{ __('mksine::marketplace.version_upgrade', ['from' => $localVersion, 'to' => $listing->version]) }}
                    </p>
                @endif

                @if ($listing->authorName !== '')
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        {{ __('mksine::marketplace.by_author') }}
                        @if ($listing->authorUrl !== '')
                            <a
                                href="{{ $listing->authorUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="font-medium text-gray-700 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400"
                            >
                                {{ $listing->authorName }}
                            </a>
                        @else
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $listing->authorName }}</span>
                        @endif
                    </p>
                @endif
            </div>
        </div>

        @include('mksine::filament.partials.marketplace-listing-actions', [
            'listing' => $listing,
            'installedVersions' => $installedVersions,
            'updatableIds' => $updatableIds,
            'canMarketplaceUpdate' => $canMarketplaceUpdate,
            'appearance' => 'directory',
        ])
    </div>

    <div @class([
        'mksine-marketplace-plugin-card-footer grid grid-cols-2 gap-x-4 gap-y-1 border-t px-5 py-2.5 text-xs',
        'border-gray-200 bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-900/60 dark:text-gray-400' => $state === 'available',
        'border-emerald-200/90 bg-emerald-100/60 text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200' => $state === 'installed',
        'border-amber-200/90 bg-amber-100/70 text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200' => $state === 'update',
    ])>
        <div>
            @if ($publishedLabel)
                {{ __('mksine::marketplace.last_updated', ['date' => $publishedLabel]) }}
            @elseif ($listing->version !== '')
                {{ __('mksine::marketplace.version_label', ['version' => $listing->version]) }}
            @endif
        </div>
        <div class="flex items-center justify-end gap-1.5">
            @if ($listing->hasRating())
                <span class="sr-only">
                    {{ __('mksine::marketplace.rating_sr', [
                        'rating' => $listing->formattedRatingAverage(),
                        'count' => $listing->ratingCount,
                    ]) }}
                </span>
                <span class="inline-flex items-center gap-0.5" aria-hidden="true">
                    @foreach ($listing->ratingStarStates() as $starState)
                        @if ($starState === 'full')
                            <x-heroicon-s-star class="h-3.5 w-3.5 text-amber-400" />
                        @else
                            <x-heroicon-o-star class="h-3.5 w-3.5 text-gray-300 dark:text-gray-600" />
                        @endif
                    @endforeach
                </span>
                <span class="tabular-nums text-gray-500 dark:text-gray-400">({{ \Illuminate\Support\Number::format($listing->ratingCount) }})</span>
            @endif
        </div>
        <div>
            @if ($publishedLabel && $listing->version !== '')
                {{ __('mksine::marketplace.version_label', ['version' => $listing->version]) }}
                @if ($listing->license !== '')
                    · {{ $listing->license }}
                @endif
            @elseif ($listing->license !== '')
                {{ $listing->license }}
            @endif
        </div>
        <div class="text-end">
            @if ($downloadLabel)
                {{ __('mksine::marketplace.downloads_count', ['count' => $downloadLabel]) }}
            @endif
        </div>
    </div>
</article>
