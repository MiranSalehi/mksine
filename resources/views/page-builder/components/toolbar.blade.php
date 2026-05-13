<div class="sticky top-0 z-20 shrink-0 border-b border-zinc-200/80 bg-white/95 px-5 py-3 backdrop-blur-md dark:border-white/[0.06] dark:bg-zinc-950/95">
    <div class="flex items-center justify-between gap-3">

        {{-- Left: identity + template --}}
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-[10px] bg-violet-600 text-white shadow-[0_2px_8px_0_rgb(124_58_237/0.35)]">
                <x-heroicon-o-squares-2x2 class="h-4.5 w-4.5" />
            </div>
            <div class="min-w-0">
                <p class="truncate text-[13px] font-semibold leading-tight text-zinc-900 dark:text-white">
                    {{ __('mksine::page_builder.title') }}
                </p>
                <p class="text-[11px] tabular-nums leading-tight text-zinc-400 dark:text-zinc-500" aria-live="polite">
                    {{ count($blocks) }}&thinsp;{{ __('mksine::page_builder.components_count') }}
                </p>
            </div>

            @if(!$showComponentPanel)
                <button
                    type="button"
                    wire:click="toggleTemplatePanel"
                    class="hidden items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-600 transition-all hover:border-zinc-300 hover:bg-zinc-50 hover:text-zinc-900 sm:inline-flex dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-zinc-400 dark:hover:border-white/[0.12] dark:hover:bg-white/[0.07] dark:hover:text-zinc-200"
                    title="{{ __('mksine::page_builder.start_from_template') }}"
                    aria-label="{{ __('mksine::page_builder.start_from_template') }}"
                >
                    <x-heroicon-o-sparkles class="h-3.5 w-3.5 text-violet-500 dark:text-violet-400" />
                    {{ __('mksine::page_builder.use_template') }}
                </button>
            @endif
        </div>

        {{-- Right: actions --}}
        <div class="flex shrink-0 items-center gap-2">

            {{-- Undo / Redo --}}
            <div class="flex items-center rounded-lg border border-zinc-200 bg-white dark:border-white/[0.08] dark:bg-white/[0.04]">
                <button
                    type="button"
                    wire:click="undo"
                    @if(!$this->canUndo()) disabled @endif
                    class="flex h-8 w-8 items-center justify-center rounded-l-lg transition-colors
                        {{ $this->canUndo()
                            ? 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-white/[0.07] dark:hover:text-zinc-100'
                            : 'cursor-not-allowed text-zinc-300 dark:text-zinc-600' }}"
                    title="{{ __('mksine::page_builder.undo') }}"
                    aria-label="{{ __('mksine::page_builder.undo') }}"
                >
                    <x-heroicon-o-arrow-uturn-left class="h-3.5 w-3.5" />
                </button>
                <div class="h-4 w-px bg-zinc-200 dark:bg-white/[0.08]"></div>
                <button
                    type="button"
                    wire:click="redo"
                    @if(!$this->canRedo()) disabled @endif
                    class="flex h-8 w-8 items-center justify-center rounded-r-lg transition-colors
                        {{ $this->canRedo()
                            ? 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-white/[0.07] dark:hover:text-zinc-100'
                            : 'cursor-not-allowed text-zinc-300 dark:text-zinc-600' }}"
                    title="{{ __('mksine::page_builder.redo') }}"
                    aria-label="{{ __('mksine::page_builder.redo') }}"
                >
                    <x-heroicon-o-arrow-uturn-right class="h-3.5 w-3.5" />
                </button>
            </div>

            {{-- Fullscreen --}}
            <button
                type="button"
                @click="fullScreen = !fullScreen"
                class="flex h-8 items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-2.5 text-xs font-medium text-zinc-600 transition-colors hover:border-zinc-300 hover:bg-zinc-50 hover:text-zinc-900 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-zinc-400 dark:hover:border-white/[0.12] dark:hover:bg-white/[0.07] dark:hover:text-zinc-200"
                :title="fullScreen ? '{{ __('mksine::page_builder.exit_full_screen') }}' : '{{ __('mksine::page_builder.full_screen') }}'"
                :aria-label="fullScreen ? '{{ __('mksine::page_builder.exit_full_screen') }}' : '{{ __('mksine::page_builder.full_screen') }}'"
            >
                <span x-show="!fullScreen" class="inline-flex"><x-heroicon-o-arrows-pointing-out class="h-3.5 w-3.5" /></span>
                <span x-show="fullScreen" class="inline-flex" x-cloak><x-heroicon-o-arrows-pointing-in class="h-3.5 w-3.5" /></span>
                <span class="hidden sm:inline" x-text="fullScreen ? '{{ __('mksine::page_builder.exit_full_screen') }}' : '{{ __('mksine::page_builder.full_screen') }}'"></span>
            </button>

            {{-- Add component -- primary CTA --}}
            <button
                type="button"
                wire:click="openComponentPanel"
                class="flex h-8 items-center gap-1.5 rounded-lg px-3 text-xs font-semibold transition-all
                    {{ $showComponentPanel
                        ? 'bg-violet-700 text-white shadow-[0_2px_8px_0_rgb(109_40_217/0.4)] dark:bg-violet-500 dark:shadow-[0_2px_8px_0_rgb(139_92_246/0.35)]'
                        : 'bg-violet-600 text-white shadow-[0_2px_8px_0_rgb(124_58_237/0.3)] hover:bg-violet-700 dark:bg-violet-600 dark:hover:bg-violet-500' }}"
                aria-label="{{ __('mksine::page_builder.add_component') }}"
            >
                <x-heroicon-o-plus class="h-3.5 w-3.5" />
                <span class="hidden sm:inline">{{ __('mksine::page_builder.add_component') }}</span>
            </button>
        </div>
    </div>
</div>
