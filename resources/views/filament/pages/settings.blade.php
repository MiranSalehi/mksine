<x-filament-panels::page>
    
    <form wire:submit="saveData">
        {{ $this->form }}
    </form>

    <x-filament-actions::modals />

</x-filament-panels::page>

