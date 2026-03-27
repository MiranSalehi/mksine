<div class="insertion-point group relative min-h-[12px] py-1">
    <div class="absolute inset-0 z-10 flex items-center justify-center gap-2 opacity-0 transition-opacity duration-150 ease-out will-change-[opacity] group-hover:opacity-100">
        <button
            type="button"
            wire:click="addBlockAtPosition({{ $position }})"
            class="insertion-btn group/btn inline-flex items-center gap-2.5 rounded-full border-2 border-dashed border-gray-300 bg-white/95 px-5 py-2.5 text-sm font-medium text-gray-600 shadow-lg shadow-gray-200/50 backdrop-blur-sm transition-all duration-150 hover:border-violet-400 hover:bg-violet-50/90 hover:text-violet-700 hover:shadow-violet-500/15 hover:scale-[1.02] active:scale-[0.98] dark:border-gray-600 dark:bg-gray-800/95 dark:text-gray-400 dark:hover:border-violet-500 dark:hover:bg-violet-900/30 dark:hover:text-violet-300"
            aria-label="{{ __('mksine::page_builder.add_component_here') }}"
        >
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-100 transition-colors group-hover/btn:bg-violet-100 dark:bg-gray-700 dark:group-hover/btn:bg-violet-800/50">
                <x-heroicon-o-plus class="h-4 w-4" />
            </span>
            <span>{{ __('mksine::page_builder.add_component_here') }}</span>
        </button>
        <button
            type="button"
            wire:click="pasteFromClipboard({{ $position }})"
            class="insertion-btn inline-flex items-center gap-2 rounded-full border border-gray-300 bg-white/90 px-3 py-2 text-xs font-medium text-gray-600 shadow transition-colors duration-150 hover:border-violet-300 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800/90 dark:text-gray-400 dark:hover:border-violet-600 dark:hover:bg-gray-700/80"
            title="{{ __('mksine::page_builder.paste_cmd') }}"
            aria-label="{{ __('mksine::page_builder.paste') }}"
        >
            <x-heroicon-o-clipboard-document class="h-4 w-4" />
            <span>{{ __('mksine::page_builder.paste') }}</span>
        </button>
    </div>
    <div class="h-px border-t-2 border-dashed border-gray-200 transition-colors duration-150 group-hover:border-violet-400/80 dark:border-gray-700 dark:group-hover:border-violet-600/70"></div>
</div>
