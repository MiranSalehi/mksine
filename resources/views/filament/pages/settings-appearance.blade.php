<x-filament-panels::page>
    @include('mksine::filament.partials.appearance-editor')

    <section class="max-w-2xl space-y-2 rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('mksine::appearance.print.heading') }}</h3>
        <label class="flex items-start gap-3">
            <x-filament::input.checkbox wire:model.live="printMonochrome" class="mt-0.5" />
            <span class="space-y-1">
                <span class="block text-sm font-medium text-gray-950 dark:text-white">{{ __('mksine::appearance.print.monochrome') }}</span>
                <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('mksine::appearance.print.monochrome_helper') }}</span>
            </span>
        </label>
    </section>

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
