<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages\Settings;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
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
use Miran\Mksine\Filament\Pages\Settings\Concerns\HasAdminMenuSettingsLinks;
use Miran\Mksine\Filament\Pages\Settings\Concerns\InteractsWithStoredSettings;

abstract class MksSettingsPage extends Page implements HasActions, HasSchemas
{
    use HasAdminMenuSettingsLinks;
    use HasPageShield;
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithStoredSettings;

    protected static ?string $cluster = SettingsCluster::class;

    protected string $view = 'mksine::filament.pages.mks-settings-page';

    /**
     * Shield permission per settings page class.
     *
     * {@see HasPageShield} caches the key in one static property, which every subclass of this
     * abstract page shares, so the first page checked in a request decided access to all others.
     *
     * @var array<class-string, string|null>
     */
    private static array $shieldPermissionKeys = [];

    /**
     * @return list<\Filament\Schemas\Components\Component>
     */
    abstract protected function settingsSchema(): array;

    protected static function getPagePermission(): ?string
    {
        if (! array_key_exists(static::class, self::$shieldPermissionKeys)) {
            $page = FilamentShield::getPages()[static::class] ?? null;

            self::$shieldPermissionKeys[static::class] = $page ? array_key_first($page['permissions']) : null;
        }

        return self::$shieldPermissionKeys[static::class];
    }

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
