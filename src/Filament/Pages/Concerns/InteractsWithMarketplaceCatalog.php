<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages\Concerns;

use Filament\Actions\Action;
use Livewire\Attributes\Url;

trait InteractsWithMarketplaceCatalog
{
    #[Url(as: 'catalog')]
    public string $catalog = 'installed';

    public function showInstalledCatalog(): void
    {
        $this->catalog = 'installed';
    }

    public function showMarketplaceCatalog(): void
    {
        $this->catalog = 'marketplace';
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
