@php
    $displayInfo = $this->getBlockDisplayInfo($block);
    $componentClass = $displayInfo['componentClass'];
    $supportsChildren = $displayInfo['supportsChildren'];
    $previewText = $displayInfo['previewText'];
@endphp

<div
    class="group relative rounded-xl border-2 border-gray-200 bg-white shadow-sm ring-1 ring-black/5 transition-all duration-200 hover:border-purple-300 hover:shadow-lg hover:ring-purple-200/50 dark:border-gray-700 dark:bg-gray-800/80 dark:ring-white/5 dark:hover:border-purple-600/60 dark:hover:ring-purple-500/20"
    data-block-id="{{ $block['id'] }}"
    x-data="{ copyFeedback: '', copySuccess: false }"
    @copy-done.window="if ($event.detail?.blockId === '{{ $block['id'] }}') { copyFeedback = $event.detail.message; copySuccess = $event.detail.success !== false; setTimeout(() => { copyFeedback = ''; copySuccess = false }, 2200) }"
>
    {{-- Block Header --}}
    <div class="flex items-center gap-3 border-b border-gray-100 px-4 py-3 dark:border-gray-700">
        <div data-sortable-handle class="cursor-grab p-1.5 text-gray-400 transition-colors hover:rounded-lg hover:bg-gray-100 hover:text-gray-600 active:cursor-grabbing dark:hover:bg-gray-700 dark:hover:text-gray-300" aria-label="Drag to reorder">
            <x-heroicon-o-bars-3 class="h-5 w-5" aria-hidden="true" />
        </div>

        <div class="flex min-w-0 flex-1 items-center gap-3">
            @if($componentClass)
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-100 to-fuchsia-100 dark:from-violet-900/40 dark:to-fuchsia-900/40">
                    <x-dynamic-component :component="$componentClass::getIcon()" class="h-4 w-4 text-violet-600 dark:text-violet-400" />
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $componentClass::getName() }}</p>
                    @if($previewText)
                        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $previewText }}</p>
                    @endif
                </div>
            @else
                <span class="text-sm text-gray-500">{{ $block['type'] }}</span>
            @endif
        </div>

        <div class="flex items-center gap-0.5 opacity-0 transition-opacity duration-200 group-hover:opacity-100">
            <button
                type="button"
                wire:click="moveBlockUp('{{ $block['id'] }}', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})"
                class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                title="{{ __('mksine::page_builder.move_up') }}"
                aria-label="{{ __('mksine::page_builder.move_up') }}"
            >
                <x-heroicon-o-chevron-up class="h-4 w-4" />
            </button>
            <button
                type="button"
                wire:click="moveBlockDown('{{ $block['id'] }}', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})"
                class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                title="{{ __('mksine::page_builder.move_down') }}"
                aria-label="{{ __('mksine::page_builder.move_down') }}"
            >
                <x-heroicon-o-chevron-down class="h-4 w-4" />
            </button>

            <div class="mx-1 h-5 w-px bg-gray-200 dark:bg-gray-600"></div>

            <button
                type="button"
                wire:click="editBlock('{{ $block['id'] }}', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})"
                class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-900/20 dark:hover:text-blue-400"
                title="{{ __('mksine::page_builder.edit') }}"
                aria-label="{{ __('mksine::page_builder.edit') }}"
            >
                <x-heroicon-o-pencil-square class="h-4 w-4" />
            </button>
            <button
                type="button"
                wire:click="duplicateBlock('{{ $block['id'] }}', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})"
                class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-emerald-50 hover:text-emerald-600 dark:hover:bg-emerald-900/20 dark:hover:text-emerald-400"
                title="{{ __('mksine::page_builder.duplicate') }}"
                aria-label="{{ __('mksine::page_builder.duplicate') }}"
            >
                <x-heroicon-o-document-duplicate class="h-4 w-4" />
            </button>
            <div class="relative inline-flex">
                <button
                    type="button"
                    @click="$wire.getBlockJsonForCopy('{{ $block['id'] }}', @js($parentId), @js($columnIndex)).then(() => { const j = $wire.copyBlockJson; if (!j) { window.dispatchEvent(new CustomEvent('copy-done', { detail: { blockId: '{{ $block['id'] }}', message: '{{ __('mksine::page_builder.copy_failed') }}', success: false } })); $wire.copyBlockJson = null; return; } const doCopy = () => { const ta = document.createElement('textarea'); ta.value = j; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); }; if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') { navigator.clipboard.writeText(j).catch(() => { doCopy(); }); } else { doCopy(); } window.dispatchEvent(new CustomEvent('copy-done', { detail: { blockId: '{{ $block['id'] }}', message: '{{ __('mksine::page_builder.copied') }}', success: true } })); $wire.copyBlockJson = null; })"
                    class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-violet-50 hover:text-violet-600 dark:hover:bg-violet-900/20 dark:hover:text-violet-400"
                    title="{{ __('mksine::page_builder.copy_cmd') }}"
                    aria-label="{{ __('mksine::page_builder.copy_cmd') }}"
                >
                    <x-heroicon-o-clipboard-document class="h-4 w-4" />
                </button>
                <span
                    x-show="copyFeedback"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-90"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    x-text="copyFeedback"
                    class="absolute bottom-full left-1/2 z-50 mb-1 -translate-x-1/2 whitespace-nowrap rounded-lg px-2.5 py-1 text-xs font-medium shadow-lg"
                    :class="copySuccess ? 'bg-emerald-600 text-white' : 'bg-danger-500 text-white'"
                    style="display: none;"
                ></span>
            </div>
            <button
                type="button"
                wire:click="removeBlock('{{ $block['id'] }}', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})"
                wire:confirm="{{ __('mksine::page_builder.delete_confirm') }}"
                class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-danger-50 hover:text-danger-600 dark:hover:bg-danger-900/20 dark:hover:text-danger-400"
                title="{{ __('mksine::page_builder.delete') }}"
                aria-label="{{ __('mksine::page_builder.delete') }}"
            >
                <x-heroicon-o-trash class="h-4 w-4" />
            </button>
        </div>
    </div>

    {{-- Children (for Columns) --}}
    @if($supportsChildren && isset($block['children']) && is_array($block['children']))
        <div class="p-4">
            <div class="grid gap-4" style="grid-template-columns: repeat({{ count($block['children']) }}, minmax(0, 1fr));">
                @foreach($block['children'] as $colIndex => $column)
                    <div class="min-h-[120px] rounded-xl border-2 border-dashed border-gray-200 bg-gray-50/50 p-3 dark:border-gray-700 dark:bg-gray-900/50">
                        <div class="mb-3 flex items-center justify-between border-b border-gray-200 pb-2 dark:border-gray-700">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $componentClass ? $componentClass::getBuilderChildRegionLabel($colIndex, count($block['children'])) : __('mksine::page_builder.column').' '.($colIndex + 1) }}</span>
                            <button
                                type="button"
                                wire:click="openComponentPanel(null, '{{ $block['id'] }}', {{ $colIndex }})"
                                class="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-violet-100 hover:text-violet-600 dark:hover:bg-violet-900/30 dark:hover:text-violet-400"
                                title="{{ __('mksine::page_builder.add_to_column') }}"
                            >
                                <x-heroicon-o-plus class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="relative min-h-[4.5rem]">
                            <div
                                class="space-y-2 min-h-[4.5rem]"
                                data-sortable-column
                                data-parent-id="{{ $block['id'] }}"
                                data-column-index="{{ $colIndex }}"
                            >
                                @foreach($column['items'] as $itemIndex => $item)
                                    @include('mksine::page-builder.partials.block-item', [
                                        'block' => $item,
                                        'index' => $itemIndex,
                                        'parentId' => $block['id'],
                                        'columnIndex' => $colIndex,
                                    ])
                                @endforeach
                            </div>
                            @if(empty($column['items']))
                                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center py-6 text-center">
                                    <div class="mb-2 flex h-12 w-12 items-center justify-center rounded-xl bg-gray-200/80 dark:bg-gray-700/50">
                                        <x-heroicon-o-inbox class="h-6 w-6 text-gray-400 dark:text-gray-500" />
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('mksine::page_builder.empty_column') }}</p>
                                    <button
                                        type="button"
                                        wire:click="openComponentPanel(0, '{{ $block['id'] }}', {{ $colIndex }})"
                                        class="pointer-events-auto mt-2 text-xs font-medium text-violet-600 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300"
                                    >
                                        + {{ __('mksine::page_builder.add_component_short') }}
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
