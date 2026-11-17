<div class="sticky top-0 z-20 shrink-0 border-b border-gray-200/80 bg-white/95 px-5 py-3 backdrop-blur-md dark:border-white/[0.06] dark:bg-gray-950/95">
    <div class="items-center gap-3" :class="fullScreen ? '' : 'flex justify-between'" :style="fullScreen ? 'display:grid;grid-template-columns:minmax(0,1fr) auto minmax(0,1fr);align-items:center' : ''">

        {{-- Left: exit + identity --}}
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                x-show="fullScreen"
                x-cloak
                @click="fullScreen = false"
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition-colors hover:bg-gray-50 hover:text-gray-900 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-gray-300 dark:hover:bg-white/[0.07]"
                title="{{ __('mksine::page_builder.exit_full_screen') }}"
                aria-label="{{ __('mksine::page_builder.exit_full_screen') }}"
            >
                <x-heroicon-o-arrows-pointing-in class="h-3.5 w-3.5" />
            </button>
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-600 text-white shadow-[0_2px_8px_0_color-mix(in_oklab,var(--primary-600)_35%,transparent)]" x-show="!fullScreen">
                <x-heroicon-o-squares-2x2 class="h-4.5 w-4.5" />
            </div>
            <div class="min-w-0" x-show="!fullScreen">
                <p class="truncate text-[13px] font-semibold leading-tight text-gray-900 dark:text-white">
                    {{ __('mksine::page_builder.title') }}
                </p>
                <p class="text-[11px] tabular-nums leading-tight text-gray-400 dark:text-gray-500" aria-live="polite">
                    {{ count($blocks) }}&thinsp;{{ __('mksine::page_builder.components_count') }}
                </p>
            </div>
            <p
                x-show="fullScreen"
                x-cloak
                class="truncate text-sm font-semibold text-gray-900 dark:text-white"
                x-text="window.__mksineBuilderPageName ? window.__mksineBuilderPageName($root) : ''"
            ></p>
        </div>

            <div class="flex items-center gap-1" x-show="fullScreen" x-cloak>
            @foreach (['desktop' => __('mksine::page_builder.viewport_desktop'), 'tablet' => __('mksine::page_builder.viewport_tablet'), 'mobile' => __('mksine::page_builder.viewport_mobile')] as $viewport => $label)
                <button
                    type="button"
                    @click="viewport = '{{ $viewport }}'"
                    :aria-pressed="viewport === '{{ $viewport }}'"
                    :class="viewport === '{{ $viewport }}' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 ring-1 ring-gray-200 dark:bg-gray-900 dark:text-gray-300 dark:ring-white/10'"
                    class="rounded-md px-2.5 py-1 text-xs font-medium"
                >{{ $label }}</button>
            @endforeach
        </div>

        {{-- Right: actions --}}
        <div class="flex shrink-0 items-center justify-end gap-2">

            @if(!$showComponentPanel)
                <button
                    type="button"
                    wire:click="toggleTemplatePanel"
                    wire:loading.attr="disabled"
                    wire:target="toggleTemplatePanel,loadTemplate"
                    class="hidden items-center justify-center gap-1.5 whitespace-nowrap rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition-all hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900 sm:inline-flex dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-gray-400 dark:hover:border-white/[0.12] dark:hover:bg-white/[0.07] dark:hover:text-gray-200"
                    title="{{ __('mksine::page_builder.start_from_template') }}"
                    aria-label="{{ __('mksine::page_builder.start_from_template') }}"
                >
                    <span class="relative inline-flex h-3.5 w-3.5 shrink-0 items-center justify-center text-primary-500 dark:text-primary-400">
                        <span wire:loading.class="opacity-0" wire:target="toggleTemplatePanel,loadTemplate">
                            <x-heroicon-o-sparkles class="h-3.5 w-3.5" />
                        </span>
                        <span
                            class="pointer-events-none absolute inset-0 hidden items-center justify-center"
                            wire:loading.flex
                            wire:target="toggleTemplatePanel,loadTemplate"
                        >
                            <x-filament::loading-indicator class="h-3.5 w-3.5 shrink-0" />
                        </span>
                    </span>
                    {{ __('mksine::page_builder.use_template') }}
                </button>
            @endif

            {{-- Undo / Redo --}}
            <div class="flex items-center rounded-lg border border-gray-200 bg-white dark:border-white/[0.08] dark:bg-white/[0.04]">
                <button
                    type="button"
                    wire:click="undo"
                    wire:loading.attr="disabled"
                    wire:target="undo"
                    @if(!$this->canUndo()) disabled @endif
                    class="relative inline-flex h-8 w-8 items-center justify-center rounded-l-lg transition-colors
                        {{ $this->canUndo()
                            ? 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/[0.07] dark:hover:text-gray-100'
                            : 'cursor-not-allowed text-gray-300 dark:text-gray-600' }}"
                    title="{{ __('mksine::page_builder.undo') }}"
                    aria-label="{{ __('mksine::page_builder.undo') }}"
                >
                    <span wire:loading.class="opacity-0" wire:target="undo">
                        <x-heroicon-o-arrow-uturn-left class="h-3.5 w-3.5" />
                    </span>
                    <span
                        class="pointer-events-none absolute inset-0 hidden items-center justify-center"
                        wire:loading.flex
                        wire:target="undo"
                    >
                        <x-filament::loading-indicator class="h-3.5 w-3.5 shrink-0" />
                    </span>
                </button>
                <div class="h-4 w-px bg-gray-200 dark:bg-white/[0.08]"></div>
                <button
                    type="button"
                    wire:click="redo"
                    wire:loading.attr="disabled"
                    wire:target="redo"
                    @if(!$this->canRedo()) disabled @endif
                    class="relative inline-flex h-8 w-8 items-center justify-center rounded-r-lg transition-colors
                        {{ $this->canRedo()
                            ? 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/[0.07] dark:hover:text-gray-100'
                            : 'cursor-not-allowed text-gray-300 dark:text-gray-600' }}"
                    title="{{ __('mksine::page_builder.redo') }}"
                    aria-label="{{ __('mksine::page_builder.redo') }}"
                >
                    <span wire:loading.class="opacity-0" wire:target="redo">
                        <x-heroicon-o-arrow-uturn-right class="h-3.5 w-3.5" />
                    </span>
                    <span
                        class="pointer-events-none absolute inset-0 hidden items-center justify-center"
                        wire:loading.flex
                        wire:target="redo"
                    >
                        <x-filament::loading-indicator class="h-3.5 w-3.5 shrink-0" />
                    </span>
                </button>
            </div>

            {{-- Fullscreen --}}
            <button
                type="button"
                @click="fullScreen = !fullScreen"
                class="flex h-8 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 text-xs font-medium text-gray-600 transition-colors hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-gray-400 dark:hover:border-white/[0.12] dark:hover:bg-white/[0.07] dark:hover:text-gray-200"
                x-show="!fullScreen"
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
                wire:loading.attr="disabled"
                wire:target="openComponentPanel"
                class="inline-flex h-8 items-center justify-center gap-1.5 whitespace-nowrap rounded-lg px-3 text-xs font-semibold transition-all
                    {{ $showComponentPanel
                        ? 'bg-primary-700 text-white shadow-[0_2px_8px_0_color-mix(in_oklab,var(--primary-700)_40%,transparent)] dark:bg-primary-500 dark:shadow-[0_2px_8px_0_color-mix(in_oklab,var(--primary-500)_35%,transparent)]'
                        : 'bg-primary-600 text-white shadow-[0_2px_8px_0_color-mix(in_oklab,var(--primary-600)_30%,transparent)] hover:bg-primary-700 dark:bg-primary-600 dark:hover:bg-primary-500' }}"
                title="{{ __('mksine::page_builder.add_component') }}"
                aria-label="{{ __('mksine::page_builder.add_component') }}"
            >
                <span class="relative inline-flex h-3.5 w-3.5 shrink-0 items-center justify-center">
                    <span wire:loading.class="opacity-0" wire:target="openComponentPanel">
                        <x-heroicon-o-plus class="h-3.5 w-3.5" />
                    </span>
                    <span
                        class="pointer-events-none absolute inset-0 hidden items-center justify-center"
                        wire:loading.flex
                        wire:target="openComponentPanel"
                    >
                        <x-filament::loading-indicator class="h-3.5 w-3.5 shrink-0" />
                    </span>
                </span>
                <span class="hidden sm:inline">{{ __('mksine::page_builder.add_component') }}</span>
            </button>
        </div>
    </div>
</div>
