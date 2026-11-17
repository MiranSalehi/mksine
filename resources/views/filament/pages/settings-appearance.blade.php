<x-filament-panels::page>
    @include('mksine::filament.partials.appearance-editor')

    <x-filament::modal id="mks-appearance-save-named" width="md" :heading="__('mksine::appearance.saved_looks.save_as')">
        <form wire:submit="saveNamedAppearance" class="space-y-3">
            <label for="mks-appearance-save-name" class="block text-sm font-medium text-gray-950 dark:text-white">
                {{ __('mksine::appearance.saved_looks.name') }}
            </label>
            <x-filament::input.wrapper :valid="! $errors->has('saveName')">
                <x-filament::input
                    id="mks-appearance-save-name"
                    type="text"
                    maxlength="{{ \Miran\Mksine\Core\Appearance\AppearanceRepository::MAX_NAME_LENGTH }}"
                    wire:model="saveName"
                    required
                />
            </x-filament::input.wrapper>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('mksine::appearance.saved_looks.hint', ['max' => \Miran\Mksine\Core\Appearance\AppearanceRepository::MAX_SAVED]) }}</p>
            @error('saveName')
                <p class="text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
            @enderror
            <x-filament::button type="submit">{{ __('mksine::appearance.saved_looks.save') }}</x-filament::button>
        </form>
    </x-filament::modal>
</x-filament-panels::page>
