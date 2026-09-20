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
    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($listing->name, 0, 1));
    $metaParts = array_values(array_filter([
        $listing->categoryName,
        $listing->version !== '' ? $listing->version : null,
        $listing->license !== '' ? $listing->license : null,
    ], static fn (?string $part): bool => is_string($part) && $part !== ''));
@endphp

<article
    data-marketplace-state="{{ $state }}"
    @class([
        'mksine-marketplace-plugin-card flex h-full flex-col overflow-hidden rounded-2xl border bg-white dark:bg-gray-900/30',
        'is-available border-gray-200 dark:border-gray-700' => $state === 'available',
        'is-installed border-emerald-300 dark:border-emerald-500/40' => $state === 'installed',
        'is-update border-amber-300 dark:border-amber-500/40' => $state === 'update',
    ])
>
    <div class="mksine-marketplace-plugin-card-media relative aspect-16/10 overflow-hidden bg-gradient-to-br from-violet-500/15 via-gray-50 to-gray-100 dark:from-violet-500/20 dark:via-gray-900 dark:to-gray-950">
        @if ($listing->imageUrl)
            @if ($listing->url !== '')
                <a href="{{ $listing->url }}" target="_blank" rel="noopener noreferrer" class="absolute inset-0">
                    <img
                        src="{{ $listing->imageUrl }}"
                        alt="{{ $listing->name }}"
                        class="h-full w-full object-cover"
                    />
                </a>
            @else
                <img
                    src="{{ $listing->imageUrl }}"
                    alt="{{ $listing->name }}"
                    class="h-full w-full object-cover"
                />
            @endif
        @else
            <span class="flex h-full items-center justify-center text-3xl font-semibold text-primary-600 dark:text-primary-300" aria-hidden="true">
                {{ $initial }}
            </span>
        @endif

        @if ($installed)
            <span @class([
                'absolute end-3 top-3 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold text-white shadow-sm',
                'bg-emerald-600' => ! $hasUpdate,
                'bg-amber-500' => $hasUpdate,
            ])>
                @if ($hasUpdate)
                    <x-heroicon-s-arrow-path class="h-3 w-3" />
                    {{ __('mksine::marketplace.update') }}
                @else
                    <x-heroicon-s-check class="h-3 w-3" />
                    {{ __('mksine::marketplace.installed_badge') }}
                @endif
            </span>
        @endif
    </div>

    <div class="flex flex-1 flex-col px-4 pb-4 pt-3">
        <h4 class="text-base font-semibold leading-snug tracking-tight text-gray-900 dark:text-white">
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
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ __('mksine::marketplace.by_author') }}
                @if ($listing->authorUrl !== '')
                    <a
                        href="{{ $listing->authorUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium text-gray-800 hover:text-primary-600 dark:text-gray-200 dark:hover:text-primary-400"
                    >
                        {{ $listing->authorName }}
                    </a>
                @else
                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ $listing->authorName }}</span>
                @endif
            </p>
        @endif

        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
            @if ($downloadLabel)
                <span>{{ __('mksine::marketplace.downloads_count', ['count' => $downloadLabel]) }}</span>
            @endif
            @if ($listing->hasRating())
                <span class="inline-flex items-center gap-1">
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
                    <span class="tabular-nums">{{ $listing->formattedRatingAverage() }}</span>
                    <span class="tabular-nums">({{ \Illuminate\Support\Number::format($listing->ratingCount) }})</span>
                </span>
            @endif
            @if ($publishedLabel)
                <span>{{ __('mksine::marketplace.last_updated', ['date' => $publishedLabel]) }}</span>
            @endif
        </div>

        @if ($metaParts !== [])
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                {{ implode(' · ', $metaParts) }}
            </p>
        @endif

        <div class="mt-auto pt-3">
            @include('mksine::filament.partials.marketplace-listing-actions', [
                'listing' => $listing,
                'installedVersions' => $installedVersions,
                'updatableIds' => $updatableIds ?? [],
                'canMarketplaceUpdate' => $canMarketplaceUpdate ?? false,
                'appearance' => 'directory',
            ])
        </div>
    </div>
</article>
