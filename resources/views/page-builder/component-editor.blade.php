<form wire:submit="save">
    <div class="px-1 py-1">
        {{ $this->form }}
    </div>

    <div class="mt-5 flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-white/10">
        <button
            type="button"
            wire:click="cancel"
            class="rounded-lg border border-zinc-200 bg-white px-3.5 py-2 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-50 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-zinc-300 dark:hover:bg-white/[0.07]"
        >{{ __('mksine::page_builder.cancel') }}</button>
        <button
            type="submit"
            class="rounded-lg bg-violet-600 px-3.5 py-2 text-xs font-semibold text-white shadow-[0_2px_6px_0_rgb(124_58_237/0.3)] transition-colors hover:bg-violet-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2"
        >{{ __('mksine::page_builder.save_changes') }}</button>
    </div>
</form>
