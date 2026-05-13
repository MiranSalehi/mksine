<div class="insertion-point group relative py-1.5">
    {{-- The visible line --}}
    <div class="h-px bg-violet-200 dark:bg-violet-500/30"></div>

    {{-- Buttons that appear centered over the line on hover --}}
    <div class="absolute inset-0 z-10 flex items-center justify-center gap-2 opacity-0 transition-opacity duration-150 will-change-[opacity] group-hover:opacity-100">
        <button
            type="button"
            wire:click="addBlockAtPosition({{ $position }})"
            class="inline-flex items-center gap-2 rounded-full border border-violet-200 bg-white px-4 py-1.5 text-xs font-semibold text-violet-700 shadow-[0_2px_8px_0_rgb(0_0_0/0.08)] transition-all hover:border-violet-300 hover:bg-violet-50 hover:text-violet-800 active:scale-[0.98] dark:border-violet-500/30 dark:bg-zinc-900 dark:text-violet-400 dark:shadow-black/40 dark:hover:border-violet-400/50 dark:hover:bg-violet-500/10"
            aria-label="{{ __('mksine::page_builder.add_component_here') }}"
        >
            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-violet-600 text-white">
                <x-heroicon-o-plus class="h-3 w-3" />
            </span>
            {{ __('mksine::page_builder.add_component_here') }}
        </button>
        <button
            type="button"
            wire:click="pasteFromClipboard({{ $position }})"
            class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-500 shadow-[0_2px_8px_0_rgb(0_0_0/0.06)] transition-colors hover:border-zinc-300 hover:bg-zinc-50 hover:text-zinc-700 dark:border-white/[0.08] dark:bg-zinc-900 dark:text-zinc-400 dark:shadow-black/30 dark:hover:bg-white/[0.07]"
            title="{{ __('mksine::page_builder.paste_cmd') }}"
            aria-label="{{ __('mksine::page_builder.paste') }}"
        >
            <x-heroicon-o-clipboard-document class="h-3.5 w-3.5" />
            {{ __('mksine::page_builder.paste') }}
        </button>
    </div>
</div>
