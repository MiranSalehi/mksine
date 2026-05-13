@if($editingBlockId)
    <x-filament::modal id="block-editor-modal" :heading="$editorHeading" width="2xl" role="dialog" aria-modal="true" :aria-labelledby="'block-editor-modal-title'">
        <div wire:key="block-editor-wrap-{{ $editingBlockId }}">
            @livewire('mksine::component-editor', [
                'blockId' => $editingBlockId,
                'blockType' => $editingBlockData['block']['type'] ?? '',
                'blockData' => $editingBlockData['block']['data'] ?? [],
                'parentId' => $editingBlockData['parentId'] ?? null,
                'columnIndex' => $editingBlockData['columnIndex'] ?? null,
            ], 'block-editor-'.$editingBlockId)
        </div>
    </x-filament::modal>
@endif

@if($showTemplatePanel)
    <x-filament::modal id="template-picker-modal" :heading="__('mksine::page_builder.choose_template')" width="4xl" role="dialog" aria-modal="true">
        @include('mksine::page-builder.partials.template-picker-content', ['templatesByCategory' => $templatesByCategory])
        <x-slot:footer>
            <x-filament::button color="gray" wire:click="closeTemplatePanel">
                {{ __('mksine::page_builder.cancel') }}
            </x-filament::button>
        </x-slot:footer>
    </x-filament::modal>
@endif

@if($showComponentPanel)
    @php $pickerItems = $this->components[$componentPickerTab] ?? []; @endphp
    <x-filament::modal
        id="component-picker-modal"
        :heading="__('mksine::page_builder.add_component')"
        width="5xl"
        role="dialog"
        aria-modal="true"
    >
        <div class="space-y-4">
            {{-- Category tabs --}}
            <div
                class="flex rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800/60"
                role="tablist"
                aria-label="{{ __('mksine::page_builder.components') }}"
            >
                <div class="-mx-0.5 flex w-full max-w-full gap-1 overflow-x-auto px-0.5 pb-0.5 sm:flex-wrap sm:overflow-visible">
                    @foreach($this->categoryDisplayMeta as $category => $meta)
                        @if(empty($this->components[$category] ?? []))
                            @continue
                        @endif
                        <button
                            type="button"
                            role="tab"
                            id="component-tab-{{ $category }}"
                            wire:click='setComponentPickerTab(@json($category))'
                            wire:loading.attr="disabled"
                            wire:target='setComponentPickerTab(@json($category))'
                            wire:key="component-picker-tabbtn-{{ $category }}"
                            aria-label="{{ $meta['name'] ?? $category }}"
                            @if($componentPickerTab === $category) aria-selected="true" @else aria-selected="false" @endif
                            class="relative inline-flex min-h-8 shrink-0 items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold outline-none transition-all duration-200 active:scale-[0.98] motion-reduce:active:scale-100
                                {{ $componentPickerTab === $category
                                    ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-zinc-50'
                                    : 'text-zinc-500 hover:bg-white/60 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-700/60 dark:hover:text-zinc-200' }}"
                        >
                            <span
                                class="inline-flex items-center gap-1.5"
                                wire:loading.delay.short.remove
                                wire:target='setComponentPickerTab(@json($category))'
                            >
                                @if(!empty($meta['icon']))
                                    <x-dynamic-component :component="$meta['icon']" class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                @endif
                                <span class="whitespace-nowrap">{{ $meta['name'] ?? $category }}</span>
                            </span>
                            <span
                                class="pointer-events-none absolute inset-0 hidden items-center justify-center rounded-md bg-inherit"
                                wire:loading.delay.short.flex
                                wire:target='setComponentPickerTab(@json($category))'
                            >
                                <x-filament::loading-indicator class="h-4 w-4 shrink-0" />
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Components grid --}}
            <div
                wire:key="component-picker-panel-{{ $componentPickerTab }}"
                class="mksine-component-picker-panel min-h-[12rem] rounded-xl border border-zinc-200/80 bg-zinc-50/50 p-3.5 dark:border-white/[0.06] dark:bg-zinc-900/30"
                role="tabpanel"
                tabindex="0"
                aria-labelledby="component-tab-{{ $componentPickerTab }}"
            >
                @if(empty($pickerItems))
                    <div class="flex flex-col items-center justify-center gap-2 py-16 text-center">
                        <x-heroicon-o-squares-2x2 class="h-9 w-9 text-zinc-300 dark:text-zinc-600" aria-hidden="true" />
                        <p class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('mksine::page_builder.no_components_in_category') }}</p>
                    </div>
                @else
                    <div class="max-h-[min(28rem,calc(100vh-14rem))] overflow-y-auto overflow-x-hidden pr-1 [scrollbar-gutter:stable]">
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($pickerItems as $info)
                                <button
                                    type="button"
                                    wire:click="addBlock('{{ $info['type'] }}', {{ $insertAtPosition ?? 'null' }}, {{ $insertInParent ? "'{$insertInParent}'" : 'null' }}, {{ $insertInColumn !== null ? $insertInColumn : 'null' }})"
                                    wire:loading.attr="disabled"
                                    wire:key="component-picker-card-{{ $componentPickerTab }}-{{ $info['type'] }}"
                                    class="group flex min-w-0 items-start gap-3 rounded-xl border border-zinc-200/80 bg-white p-3.5 text-start shadow-[0_1px_2px_0_rgb(0_0_0/0.04)] transition-all duration-150 hover:border-violet-200 hover:shadow-[0_4px_12px_0_rgb(124_58_237/0.1)] focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 dark:border-white/[0.07] dark:bg-zinc-900 dark:shadow-none dark:hover:border-violet-500/30 dark:focus-visible:ring-violet-400"
                                >
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-[10px] bg-zinc-100 text-zinc-500 transition-colors group-hover:bg-violet-50 group-hover:text-violet-600 dark:bg-white/[0.06] dark:text-zinc-400 dark:group-hover:bg-violet-500/10 dark:group-hover:text-violet-400">
                                        <x-dynamic-component :component="$info['icon']" class="h-5 w-5" aria-hidden="true" />
                                    </div>
                                    <div class="min-w-0 flex-1 pt-0.5">
                                        <span class="block text-[13px] font-semibold text-zinc-900 dark:text-zinc-100">{{ $info['name'] }}</span>
                                        @if(!empty($info['description']))
                                            <span class="mt-0.5 block text-[11px] leading-relaxed text-zinc-400 line-clamp-2 dark:text-zinc-500">{{ $info['description'] }}</span>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <x-slot:footer>
            <x-filament::button color="gray" wire:click="closeComponentPanel">
                {{ __('mksine::page_builder.cancel') }}
            </x-filament::button>
        </x-slot:footer>
    </x-filament::modal>
@endif
