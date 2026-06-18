<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages\Settings;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Miran\Mksine\Core\Theme\ThemeBootstrap;
use Miran\Mksine\Filament\Clusters\SettingsCluster;
use Miran\Mksine\Filament\Pages\Settings\Concerns\InteractsWithStoredSettings;
use Miran\Mksine\Filament\Support\AdminSidebarNavigation;

abstract class MksSettingsPage extends Page implements HasActions, HasSchemas
{
    use HasPageShield;
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithStoredSettings;

    protected static ?string $cluster = SettingsCluster::class;

    protected string $view = 'mksine::filament.pages.mks-settings-page';

    public static function getCluster(): ?string
    {
        if (
            AdminSidebarNavigation::usesShopSidebar()
            && str_starts_with(static::class, 'Mksine\\Ecom\\')
        ) {
            return null;
        }

        return static::$cluster;
    }

    /**
     * @return list<\Filament\Schemas\Components\Component>
     */
    abstract protected function settingsSchema(): array;

    public function getTitle(): string | Htmlable
    {
        return static::getNavigationLabel();
    }

    public function mount(): void
    {
        app(ThemeBootstrap::class)->boot();

        $this->loadStoredSettingsIntoForm();

        $this->form->fill($this->data);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema($this->settingsSchema())
            ->inlineLabel()
            ->statePath('data');
    }

    protected function getActions(): array
    {
        return [
            Action::make('save-data')
                ->label(__('mksine::settings.save'))
                ->action('saveData'),
        ];
    }
}
