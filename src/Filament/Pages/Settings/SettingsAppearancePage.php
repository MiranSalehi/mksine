<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages\Settings;

use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Miran\Mksine\Core\Appearance\Appearance;
use Miran\Mksine\Core\Appearance\AppearanceRepository;
use Miran\Mksine\Core\Appearance\PrintColors;
use Miran\Mksine\Filament\Concerns\InteractsWithAppearanceEditor;

/**
 * Settings → Appearance: the company look (everyone's default), the company's named looks and a live
 * preview. Opening the page needs `View:SettingsAppearancePage`; every write checks it again.
 */
class SettingsAppearancePage extends MksSettingsPage
{
    use InteractsWithAppearanceEditor;

    protected static ?int $navigationSort = 15;

    protected string $view = 'mksine::filament.pages.settings-appearance';

    public string $saveName = '';

    public bool $printMonochrome = false;

    public static function getNavigationLabel(): string
    {
        return __('mksine::appearance.company.navigation_label');
    }

    public function mount(): void
    {
        $this->fillAppearanceEditor(app(AppearanceRepository::class)->company());
        $this->printMonochrome = app(PrintColors::class)->isMonochrome();
    }

    /**
     * "Print without colour" is an ordinary company setting, saved as soon as it is switched.
     */
    public function updatedPrintMonochrome(): void
    {
        $this->authorizeCompanyEdit();

        app(PrintColors::class)->setMonochrome($this->printMonochrome);

        Notification::make()->title(__('mksine::appearance.print.saved'))->success()->send();
    }

    protected function settingsSchema(): array
    {
        return [];
    }

    protected function getActions(): array
    {
        return [];
    }

    public function isPersonalEditor(): bool
    {
        return false;
    }

    protected function appearanceBase(): Appearance
    {
        return Appearance::empty();
    }

    protected function persistAppearance(Appearance $appearance): void
    {
        $this->authorizeCompanyEdit();

        app(AppearanceRepository::class)->saveCompany($appearance);

        Notification::make()->title(__('mksine::appearance.company.saved'))->success()->send();
    }

    /**
     * Back to the app's own look (nothing stored until saved).
     */
    public function resetToAppDefault(): void
    {
        $this->authorizeCompanyEdit();

        $this->applyLook(Appearance::empty());
    }

    public function saveNamedAppearance(): void
    {
        $this->authorizeCompanyEdit();

        try {
            app(AppearanceRepository::class)->saveNamed($this->saveName, $this->editedAppearance());
        } catch (ValidationException $exception) {
            $this->addError('saveName', (string) collect($exception->errors())->flatten()->first());

            return;
        }

        $this->look['preset'] = 'saved:' . trim((string) preg_replace('/\s+/u', ' ', $this->saveName));
        $this->saveName = '';
        $this->dispatch('close-modal', id: 'mks-appearance-save-named');

        Notification::make()->title(__('mksine::appearance.saved_looks.saved'))->success()->send();
    }

    public function deleteNamedAppearance(string $name): void
    {
        $this->authorizeCompanyEdit();

        app(AppearanceRepository::class)->deleteNamed($name);

        if (($this->look['preset'] ?? null) === 'saved:' . $name) {
            $this->look['preset'] = null;
        }

        Notification::make()->title(__('mksine::appearance.saved_looks.deleted'))->success()->send();
    }

    private function authorizeCompanyEdit(): void
    {
        abort_unless(AppearanceRepository::canEditCompany(auth()->user()), 403);
    }
}
