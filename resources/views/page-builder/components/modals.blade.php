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
    @php
        $pickerItems = $this->components[$componentPickerTab] ?? [];
    @endphp
    <x-filament::modal
        id="component-picker-modal"
        :heading="__('mksine::page_builder.add_component')"
        width="5xl"
        role="dialog"
        aria-modal="true"
    >
        <div class="space-y-5">
            <div
                class="rounded-xl bg-gray-50/90 p-1.5 ring-1 ring-gray-200/80 dark:bg-gray-900/50 dark:ring-gray-700/80"
                role="tablist"
                aria-label="{{ __('mksine::page_builder.components') }}"
            >
                <div class="-mx-0.5 flex max-w-full gap-1 overflow-x-auto px-0.5 pb-0.5 sm:flex-wrap sm:overflow-visible">
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
                            class="relative inline-flex min-h-9 shrink-0 items-center justify-center overflow-hidden rounded-lg px-3.5 py-2 text-sm font-medium outline-none transition-[color,background-color,box-shadow,transform] duration-300 ease-out will-change-transform active:scale-[0.98] motion-reduce:transition-none motion-reduce:active:scale-100 {{ $componentPickerTab === $category ? 'bg-white text-violet-800 shadow-sm ring-1 ring-gray-200/90 dark:bg-gray-800 dark:text-violet-200 dark:ring-gray-600' : 'text-gray-600 hover:bg-white/70 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/80 dark:hover:text-gray-200' }}"
                        >
                            <span
                                class="inline-flex items-center gap-2"
                                wire:loading.delay.short.remove
                                wire:target='setComponentPickerTab(@json($category))'
                            >
                                @if(! empty($meta['icon']))
                                    <x-dynamic-component :component="$meta['icon']" class="h-4 w-4 shrink-0 opacity-90" aria-hidden="true" />
                                @endif
                                <span class="whitespace-nowrap">{{ $meta['name'] ?? $category }}</span>
                            </span>
                            <span
                                class="pointer-events-none absolute inset-0 hidden items-center justify-center rounded-lg bg-inherit"
                                wire:loading.delay.short.flex
                                wire:target='setComponentPickerTab(@json($category))'
                            >
                                <x-filament::loading-indicator class="h-5 w-5 shrink-0 text-violet-600 dark:text-violet-400" />
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            <div
                wire:key="component-picker-panel-{{ $componentPickerTab }}"
                class="mksine-component-picker-panel min-h-[12rem] rounded-xl border border-gray-100 bg-gradient-to-b from-gray-50/40 to-transparent p-4 dark:border-gray-700/80 dark:from-gray-800/30"
                role="tabpanel"
                tabindex="0"
                aria-labelledby="component-tab-{{ $componentPickerTab }}"
            >
                @if(empty($pickerItems))
                    <div class="flex flex-col items-center justify-center gap-2 py-16 text-center text-sm text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-squares-2x2 class="h-10 w-10 opacity-40" aria-hidden="true" />
                        <p>{{ __('mksine::page_builder.no_components_in_category') }}</p>
                    </div>
                @else
                    <div class="max-h-[min(28rem,calc(100vh-14rem))] overflow-y-auto overflow-x-hidden pr-1 [-ms-overflow-style:thin] [scrollbar-gutter:stable]">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($pickerItems as $info)
                                <button
                                    type="button"
                                    wire:click="addBlock('{{ $info['type'] }}', {{ $insertAtPosition ?? 'null' }}, {{ $insertInParent ? "'{$insertInParent}'" : 'null' }}, {{ $insertInColumn !== null ? $insertInColumn : 'null' }})"
                                    wire:loading.attr="disabled"
                                    wire:key="component-picker-card-{{ $componentPickerTab }}-{{ $info['type'] }}"
                                    class="group flex min-w-0 flex-col items-start gap-2 rounded-xl border border-gray-200/90 bg-white p-4 text-start transition-colors duration-200 hover:border-violet-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500/50 dark:border-gray-600 dark:bg-gray-900/60 dark:hover:border-violet-500/70"
                                >
                                    <div class="flex w-full items-start gap-3">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-100 to-fuchsia-100/80 ring-1 ring-violet-200/50 dark:from-violet-950/50 dark:to-fuchsia-950/30 dark:ring-violet-800/40">
                                            <x-dynamic-component :component="$info['icon']" class="h-6 w-6 text-violet-600 dark:text-violet-300" aria-hidden="true" />
                                        </div>
                                        <div class="min-w-0 flex-1 pt-0.5">
                                            <span class="block text-sm font-semibold text-gray-900 group-hover:text-violet-900 dark:text-white dark:group-hover:text-violet-100">{{ $info['name'] }}</span>
                                            @if(! empty($info['description']))
                                                <span class="mt-1 block text-xs leading-relaxed text-gray-500 line-clamp-2 dark:text-gray-400">{{ $info['description'] }}</span>
                                            @endif
                                        </div>
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
