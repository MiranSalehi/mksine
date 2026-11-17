<?php

declare(strict_types=1);

namespace Miran\Mksine\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Miran\Mksine\Core\Appearance\Appearance;
use Miran\Mksine\Core\Appearance\AppearanceRepository;
use Miran\Mksine\Filament\Concerns\InteractsWithAppearanceEditor;

/**
 * "My appearance" window (user menu): the signed-in person changes any part of the look for
 * themselves only; empty parts come from the company look.
 */
class MyAppearance extends Component
{
    use InteractsWithAppearanceEditor;

    public const string MODAL_ID = 'mks-my-appearance';

    public function mount(): void
    {
        $this->loadPersonalLook();
    }

    public function isPersonalEditor(): bool
    {
        return true;
    }

    /**
     * Fresh state each time the window opens (another tab may have saved meanwhile).
     */
    public function loadPersonalLook(): void
    {
        $user = auth()->user();

        $this->fillAppearanceEditor(
            $user === null ? Appearance::empty() : app(AppearanceRepository::class)->personal($user->getAuthIdentifier()),
        );
    }

    protected function appearanceBase(): Appearance
    {
        return app(AppearanceRepository::class)->company();
    }

    protected function persistAppearance(Appearance $appearance): void
    {
        $user = auth()->user();
        abort_if($user === null, 403);

        app(AppearanceRepository::class)->saveForUser($user->getAuthIdentifier(), $appearance);

        Notification::make()->title(__('mksine::appearance.my.saved'))->success()->send();
    }

    /**
     * "Same as the company": forget every personal part.
     */
    public function useCompanyLook(): void
    {
        $user = auth()->user();
        abort_if($user === null, 403);

        app(AppearanceRepository::class)->resetUser($user->getAuthIdentifier());

        Notification::make()->title(__('mksine::appearance.my.reset_done'))->success()->send();

        $this->js('window.location.reload()');
    }

    public function render(): View
    {
        return view('mksine::livewire.my-appearance');
    }
}
