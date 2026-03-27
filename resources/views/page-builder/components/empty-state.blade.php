<div class="relative overflow-hidden rounded-2xl border-2 border-dashed border-purple-200/80 bg-gradient-to-br from-gray-50 via-purple-50/40 to-fuchsia-50/30 py-20 px-8 text-center ring-1 ring-purple-100/50 dark:border-purple-800/50 dark:from-gray-900/50 dark:via-purple-950/30 dark:to-fuchsia-950/20 dark:ring-purple-900/30">
    <div class="pointer-events-none absolute inset-0 flex items-center justify-center opacity-[0.06] dark:opacity-[0.04]">
        <x-heroicon-o-squares-2x2 class="h-72 w-72 text-purple-600" aria-hidden="true" />
    </div>
    <div class="relative">
        <div class="mb-6 inline-flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 via-purple-600 to-fuchsia-500 shadow-xl shadow-purple-500/30 ring-4 ring-purple-400/20 dark:ring-purple-500/30">
            <x-heroicon-o-document-plus class="h-10 w-10 text-white" aria-hidden="true" />
        </div>
        <h3 class="mb-2 text-xl font-bold tracking-tight text-gray-900 dark:text-white">
            {{ __('mksine::page_builder.your_page_is_empty') }}
        </h3>
        <p class="mx-auto mb-8 max-w-sm text-sm leading-relaxed text-gray-600 dark:text-gray-400">
            {{ __('mksine::page_builder.start_building_by_adding') }}
        </p>
        <button
            type="button"
            wire:click="addBlockAtPosition(0)"
            class="inline-flex items-center gap-2.5 rounded-xl bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-purple-500/30 ring-2 ring-purple-400/30 transition-all duration-200 hover:shadow-xl hover:shadow-purple-500/40 hover:scale-[1.02] active:scale-[0.98]"
            aria-label="{{ __('mksine::page_builder.add_component') }}"
        >
            <x-heroicon-o-plus class="h-5 w-5" aria-hidden="true" />
            {{ __('mksine::page_builder.add_component') }}
        </button>
    </div>
</div>
