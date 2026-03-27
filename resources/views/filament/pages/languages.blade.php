<x-filament-panels::page>
    <form wire:submit="saveTranslations">
        {{ $this->form }}
    </form>

    <x-filament-actions::modals />
</x-filament-panels::page>
