<div wire:key="block-editor-form">
    <div class="mksine-block-editor-form px-1 py-1 pb-2">
        {{ $this->form }}
    </div>

    <div class="mt-5 flex items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-white/10">
        <button
            type="button"
            wire:click="cancel"
            wire:loading.attr="disabled"
            wire:target="cancel,save"
            class="relative inline-flex items-center justify-center whitespace-nowrap rounded-lg border border-zinc-200 bg-white px-3.5 py-2 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-50 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-zinc-300 dark:hover:bg-white/[0.07]"
        >
            <span wire:loading.class="opacity-0" wire:target="cancel">{{ __('mksine::page_builder.cancel') }}</span>
            <span
                class="pointer-events-none absolute inset-0 hidden items-center justify-center"
                wire:loading.flex
                wire:target="cancel"
            >
                <x-filament::loading-indicator class="h-3.5 w-3.5" />
            </span>
        </button>
        <button
            type="button"
            wire:click="save"
            wire:loading.attr="disabled"
            wire:target="save,cancel"
            class="relative inline-flex items-center justify-center whitespace-nowrap rounded-lg bg-violet-600 px-3.5 py-2 text-xs font-semibold text-white shadow-[0_2px_6px_0_rgb(124_58_237/0.3)] transition-colors hover:bg-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2"
        >
            <span wire:loading.class="opacity-0" wire:target="save">{{ __('mksine::page_builder.save_changes') }}</span>
            <span
                class="pointer-events-none absolute inset-0 hidden items-center justify-center"
                wire:loading.flex
                wire:target="save"
            >
                <x-filament::loading-indicator class="h-3.5 w-3.5" />
            </span>
        </button>
    </div>
</div>
