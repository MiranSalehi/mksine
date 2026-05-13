<div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-violet-200 bg-violet-50/20 py-20 px-8 text-center dark:border-violet-500/25 dark:bg-violet-500/[0.03]">
    <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 ring-1 ring-violet-100 dark:bg-violet-500/10 dark:text-violet-400 dark:ring-violet-500/20">
        <x-heroicon-o-document-plus class="h-7 w-7" aria-hidden="true" />
    </div>
    <h3 class="mb-1.5 text-[15px] font-semibold tracking-tight text-zinc-900 dark:text-white">
        {{ __('mksine::page_builder.your_page_is_empty') }}
    </h3>
    <p class="mx-auto mb-7 max-w-xs text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
        {{ __('mksine::page_builder.start_building_by_adding') }}
    </p>
    <button
        type="button"
        wire:click="addBlockAtPosition(0)"
        class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-[0_2px_8px_0_rgb(124_58_237/0.3)] transition-colors hover:bg-violet-700"
        aria-label="{{ __('mksine::page_builder.add_component') }}"
    >
        <x-heroicon-o-plus class="h-4 w-4" aria-hidden="true" />
        {{ __('mksine::page_builder.add_component') }}
    </button>
</div>
