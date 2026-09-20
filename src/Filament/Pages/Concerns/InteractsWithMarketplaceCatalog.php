<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages\Concerns;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Attributes\Url;
use Miran\Mksine\Core\Marketplace\DownloadMarketplaceArchive;
use Miran\Mksine\Core\Marketplace\MarketplaceCatalogClient;
use Miran\Mksine\Core\Marketplace\MarketplaceCatalogResult;
use Miran\Mksine\Core\Marketplace\MarketplaceException;
use Miran\Mksine\Core\Marketplace\MarketplaceKind;
use Miran\Mksine\Core\Marketplace\MarketplaceRelease;
use Miran\Mksine\Core\Updater\SuperAdminGate;

trait InteractsWithMarketplaceCatalog
{
    #[Url(as: 'catalog')]
    public string $catalog = 'installed';

    public string $marketplaceSearch = '';

    public int $marketplacePage = 1;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $marketplaceSnapshot = null;

    abstract protected function marketplaceKind(): MarketplaceKind;

    /**
     * @return list<string>
     */
    abstract public function marketplaceInstalledPackageIds(): array;

    /**
     * @return array<string, string>
     */
    abstract public function marketplaceInstalledVersions(): array;

    /**
     * @return list<string>
     */
    abstract public function marketplaceUpdatablePackageIds(): array;

    abstract protected function installMarketplaceZip(string $path): void;

    abstract protected function applyMarketplaceUpdate(string $packageId, string $zipPath): void;

    public function showInstalledCatalog(): void
    {
        $this->catalog = 'installed';
    }

    public function showMarketplaceCatalog(): void
    {
        $this->catalog = 'marketplace';
        $this->hydrateMarketplaceCatalog();
    }

    public function isMarketplaceCatalog(): bool
    {
        return $this->catalog === 'marketplace';
    }

    public function updatedCatalog(mixed $value): void
    {
        if (! in_array($value, ['installed', 'marketplace'], true)) {
            $this->catalog = 'installed';
        }
    }

    public function updatedMarketplaceSearch(): void
    {
        $this->marketplacePage = 1;
        $this->marketplaceSnapshot = null;
    }

    public function goToMarketplacePage(int $page): void
    {
        $this->marketplacePage = max(1, $page);
        $this->marketplaceSnapshot = null;
    }

    public function loadMarketplaceCatalog(): void
    {
        $this->catalog = 'marketplace';

        if ($this->marketplaceCatalogIsCurrent()) {
            $this->skipRender();

            return;
        }

        $this->hydrateMarketplaceCatalog();
    }

    protected function marketplaceCatalogFingerprint(): string
    {
        return $this->marketplaceKind()->value.'|'.trim($this->marketplaceSearch).'|'.$this->marketplacePage;
    }

    protected function marketplaceCatalogIsCurrent(): bool
    {
        return is_array($this->marketplaceSnapshot)
            && ($this->marketplaceSnapshot['_key'] ?? null) === $this->marketplaceCatalogFingerprint();
    }

    protected function hydrateMarketplaceCatalog(): void
    {
        if ($this->marketplaceCatalogIsCurrent()) {
            return;
        }

        $result = app(MarketplaceCatalogClient::class)->index(
            $this->marketplaceKind(),
            trim($this->marketplaceSearch),
            $this->marketplacePage,
        );

        $snapshot = $result->toArray();
        $snapshot['_key'] = $this->marketplaceCatalogFingerprint();
        $this->marketplaceSnapshot = $snapshot;
    }

    public function retryMarketplaceCatalog(): void
    {
        $this->marketplaceSnapshot = null;
        app(MarketplaceCatalogClient::class)->forgetIndex(
            $this->marketplaceKind(),
            trim($this->marketplaceSearch),
            $this->marketplacePage,
        );
        $this->loadMarketplaceCatalog();
    }

    public function marketplaceListings(): ?MarketplaceCatalogResult
    {
        if (! is_array($this->marketplaceSnapshot)) {
            return null;
        }

        return MarketplaceCatalogResult::fromSnapshot($this->marketplaceSnapshot, $this->marketplaceKind());
    }

    public function isMarketplacePackageInstalled(string $packageId): bool
    {
        return in_array($packageId, $this->marketplaceInstalledPackageIds(), true);
    }

    public function marketplaceInstalledVersion(string $packageId): ?string
    {
        $version = $this->marketplaceInstalledVersions()[$packageId] ?? null;

        return is_string($version) && $version !== '' ? $version : null;
    }

    public function canRunMarketplaceUpdates(): bool
    {
        return SuperAdminGate::check() && (bool) config('mksine.updater.enabled', true);
    }

    public function canUpdateMarketplaceListing(string $packageId, string $catalogVersion): bool
    {
        $installed = $this->marketplaceInstalledVersion($packageId);

        if ($installed === null || ! MarketplaceRelease::isNewer($catalogVersion, $installed)) {
            return false;
        }

        return $this->canRunMarketplaceUpdates()
            && in_array($packageId, $this->marketplaceUpdatablePackageIds(), true);
    }

    public function installFromMarketplace(string $slug): void
    {
        $path = null;

        try {
            $path = app(DownloadMarketplaceArchive::class)->handle($this->marketplaceKind(), $slug);
            $this->installMarketplaceZip($path);
        } catch (MarketplaceException $e) {
            Notification::make()
                ->title(__('mksine::marketplace.install_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            if (is_string($path) && is_file($path)) {
                @unlink($path);
            }
        }
    }

    public function updateFromMarketplace(string $slug): void
    {
        SuperAdminGate::authorize();

        if (! (bool) config('mksine.updater.enabled', true)) {
            return;
        }

        $path = null;

        try {
            $listing = app(MarketplaceCatalogClient::class)->show($this->marketplaceKind(), $slug);

            if (! $this->canUpdateMarketplaceListing($listing->packageId, $listing->version)) {
                Notification::make()
                    ->title(__('mksine::marketplace.update_failed'))
                    ->body(__('mksine::marketplace.update_not_allowed'))
                    ->danger()
                    ->send();

                return;
            }

            $path = app(DownloadMarketplaceArchive::class)->handle($this->marketplaceKind(), $slug);
            $this->applyMarketplaceUpdate($listing->packageId, $path);
        } catch (MarketplaceException $e) {
            Notification::make()
                ->title(__('mksine::marketplace.update_failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            if (is_string($path) && is_file($path)) {
                @unlink($path);
            }
        }
    }

    protected function browseMarketplaceAction(): Action
    {
        return Action::make('browseMarketplace')
            ->label(__('mksine::marketplace.tab_add'))
            ->icon('heroicon-o-squares-plus')
            ->color('gray')
            ->visible(fn (): bool => ! $this->isMarketplaceCatalog())
            ->action(function (): void {
                $this->showMarketplaceCatalog();
            });
    }
}
