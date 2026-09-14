<form wire:submit="save">
    <div class="mksine-block-editor-form px-1 py-1 pb-2">
        {{ $this->form }}
    </div>

    <div class="mt-5 flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-white/10">
        <button
            type="button"
            wire:click="cancel"
            wire:loading.attr="disabled"
            wire:target="cancel,save"
            class="inline-flex min-w-[6.5rem] items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-zinc-200 bg-white px-3.5 py-2 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-50 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-zinc-300 dark:hover:bg-white/[0.07]"
        >
            <span class="inline-flex h-3.5 w-3.5 shrink-0 items-center justify-center" aria-hidden="true">
                <x-filament::loading-indicator
                    class="h-3.5 w-3.5 opacity-0"
                    wire:loading.class.remove="opacity-0"
                    wire:target="cancel"
                />
            </span>
            <span>{{ __('mksine::page_builder.cancel') }}</span>
        </button>
        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="save,cancel"
            class="inline-flex min-w-[9.5rem] items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-violet-600 px-3.5 py-2 text-xs font-semibold text-white shadow-[0_2px_6px_0_rgb(124_58_237/0.3)] transition-colors hover:bg-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2"
        >
            <span class="inline-flex h-3.5 w-3.5 shrink-0 items-center justify-center" aria-hidden="true">
                <x-filament::loading-indicator
                    class="h-3.5 w-3.5 opacity-0"
                    wire:loading.class.remove="opacity-0"
                    wire:target="save"
                />
            </span>
            <span>{{ __('mksine::page_builder.save_changes') }}</span>
        </button>
    </div>
</form>
