<div
    x-on:open-modal.window="if ($event.detail.id === @js(\Miran\Mksine\Livewire\MyAppearance::MODAL_ID)) { $wire.loadPersonalLook() }"
    x-on:modal-closed.window="if ($event.detail.id === @js(\Miran\Mksine\Livewire\MyAppearance::MODAL_ID)) { $dispatch('mks-appearance-restore', { editor: @js($this->getId()) }) }"
>
    <x-filament::modal
        :id="\Miran\Mksine\Livewire\MyAppearance::MODAL_ID"
        width="6xl"
        :heading="__('mksine::appearance.my.heading')"
        :description="__('mksine::appearance.my.description')"
    >
        @include('mksine::filament.partials.appearance-editor')
    </x-filament::modal>
</div>
