<div class="sticky top-0 z-20 shrink-0 border-b border-gray-200/80 bg-white/95 px-4 py-3 backdrop-blur-md dark:border-gray-700/80 dark:bg-gray-900/95">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 via-purple-500 to-fuchsia-500 shadow-lg shadow-purple-500/25 ring-2 ring-purple-400/20">
                <x-heroicon-o-squares-2x2 class="h-5 w-5 text-white" />
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('mksine::page_builder.title') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400" aria-live="polite">
                    {{ count($blocks) }} {{ __('mksine::page_builder.components_count') }}
                </p>
            </div>
            @if(!$showComponentPanel)
                <button
                    type="button"
                    wire:click="toggleTemplatePanel"
                    class="group inline-flex items-center gap-2 rounded-xl border border-purple-200 bg-gradient-to-r from-purple-50 to-fuchsia-50 px-4 py-2.5 text-sm font-semibold text-purple-700 shadow-sm transition-all duration-200 hover:from-purple-100 hover:to-fuchsia-100 hover:shadow-md hover:shadow-purple-500/10 dark:border-purple-800/60 dark:from-purple-900/30 dark:to-fuchsia-900/20 dark:text-purple-300 dark:hover:from-purple-800/40 dark:hover:to-fuchsia-800/30"
                    title="{{ __('mksine::page_builder.start_from_template') }}"
                    aria-label="{{ __('mksine::page_builder.start_from_template') }}"
                >
                    <x-heroicon-o-sparkles class="h-4 w-4 text-purple-500 dark:text-purple-400" />
                    {{ __('mksine::page_builder.use_template') }}
                    <x-heroicon-s-chevron-down class="h-4 w-4 transition-transform group-hover:translate-y-0.5" />
                </button>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <div class="flex items-center gap-0.5 rounded-lg border border-gray-200 bg-gray-50/80 px-1 py-1 dark:border-gray-700 dark:bg-gray-800/80">
                <button
                    type="button"
                    wire:click="undo"
                    @if(!$this->canUndo()) disabled @endif
                    class="rounded-md p-2 transition-colors {{ $this->canUndo() ? 'text-gray-700 hover:bg-white hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' : 'cursor-not-allowed text-gray-400 opacity-50 dark:text-gray-600' }}"
                    title="{{ __('mksine::page_builder.undo') }}"
                    aria-label="{{ __('mksine::page_builder.undo') }}"
                >
                    <x-heroicon-o-arrow-uturn-left class="h-4 w-4" />
                </button>
                <button
                    type="button"
                    wire:click="redo"
                    @if(!$this->canRedo()) disabled @endif
                    class="rounded-md p-2 transition-colors {{ $this->canRedo() ? 'text-gray-700 hover:bg-white hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' : 'cursor-not-allowed text-gray-400 opacity-50 dark:text-gray-600' }}"
                    title="{{ __('mksine::page_builder.redo') }}"
                    aria-label="{{ __('mksine::page_builder.redo') }}"
                >
                    <x-heroicon-o-arrow-uturn-right class="h-4 w-4" />
                </button>
            </div>

            <button
                type="button"
                wire:click="openComponentPanel"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium transition-all duration-200 {{ $showComponentPanel ? 'bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 text-white shadow-lg shadow-purple-500/30 ring-2 ring-purple-400/30' : 'border border-gray-300 bg-white text-gray-700 hover:border-purple-300 hover:bg-purple-50/50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-purple-600 dark:hover:bg-purple-900/30' }}"
                aria-label="{{ __('mksine::page_builder.add_component') }}"
            >
                <x-heroicon-o-plus class="h-4 w-4" />
                {{ __('mksine::page_builder.add_component') }}
            </button>

            <button
                type="button"
                @click="fullScreen = !fullScreen"
                class="inline-flex items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                :title="fullScreen ? '{{ __('mksine::page_builder.exit_full_screen') }}' : '{{ __('mksine::page_builder.full_screen') }}'"
                :aria-label="fullScreen ? '{{ __('mksine::page_builder.exit_full_screen') }}' : '{{ __('mksine::page_builder.full_screen') }}'"
            >
                <span x-show="!fullScreen" class="inline-flex"><x-heroicon-o-arrows-pointing-out class="h-4 w-4" /></span>
                <span x-show="fullScreen" class="inline-flex" x-cloak><x-heroicon-o-arrows-pointing-in class="h-4 w-4" /></span>
                <span x-text="fullScreen ? '{{ __('mksine::page_builder.exit_full_screen') }}' : '{{ __('mksine::page_builder.full_screen') }}'"></span>
            </button>

            @if(!$showComponentPanel)
                <button
                    type="button"
                    @click="openPreview()"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 via-purple-600 to-fuchsia-600 px-4 py-2.5 text-sm font-medium text-white shadow-lg shadow-purple-500/30 transition-all hover:shadow-purple-500/40"
                    aria-label="{{ __('mksine::page_builder.preview') }}"
                >
                    <x-heroicon-o-eye class="h-4 w-4" />
                    {{ __('mksine::page_builder.preview') }}
                </button>
            @endif
        </div>
    </div>
</div>
