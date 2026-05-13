@php
    $displayInfo = $this->getBlockDisplayInfo($block);
    $componentClass = $displayInfo['componentClass'];
    $supportsChildren = $displayInfo['supportsChildren'];
    $previewText = $displayInfo['previewText'];
@endphp

<div
    wire:key="block-{{ $block['id'] ?? $index }}"
    class="group relative rounded-xl border border-zinc-200/80 bg-white shadow-[0_1px_3px_0_rgb(0_0_0/0.06),0_1px_2px_-1px_rgb(0_0_0/0.06)] transition-[border-color,box-shadow] duration-150 hover:border-violet-300 hover:shadow-[0_4px_12px_0_rgb(0_0_0/0.08)] dark:border-white/[0.07] dark:bg-zinc-900 dark:shadow-none dark:hover:border-violet-500/50"
    data-block-id="{{ $block['id'] }}"
    x-data="{ copyFeedback: '', copySuccess: false }"
    @copy-done.window="if ($event.detail?.blockId === '{{ $block['id'] }}') { copyFeedback = $event.detail.message; copySuccess = $event.detail.success !== false; setTimeout(() => { copyFeedback = ''; copySuccess = false }, 2200) }"
>
    {{-- Header --}}
    <div class="flex items-center gap-2.5 px-4 py-2.5">

        {{-- Drag handle --}}
        <div
            data-sortable-handle
            class="shrink-0 cursor-grab rounded-md p-1 text-zinc-300 transition-colors hover:bg-zinc-100 hover:text-zinc-500 active:cursor-grabbing dark:text-zinc-600 dark:hover:bg-white/[0.06] dark:hover:text-zinc-400"
            aria-label="Drag to reorder"
        >
            <x-heroicon-o-bars-3 class="h-4 w-4" aria-hidden="true" />
        </div>

        {{-- Icon --}}
        @if($componentClass)
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[8px] bg-violet-50 text-violet-600 ring-1 ring-violet-100 dark:bg-violet-500/10 dark:text-violet-400 dark:ring-violet-500/20">
                <x-dynamic-component :component="$componentClass::getIcon()" class="h-4 w-4" />
            </div>
        @endif

        {{-- Name / preview --}}
        <div class="min-w-0 flex-1">
            @if($componentClass)
                <p class="truncate text-[13px] font-semibold leading-tight text-zinc-900 dark:text-zinc-100">
                    {{ $componentClass::getName() }}
                </p>
                @if($previewText)
                    <p class="mt-0.5 truncate text-[11px] leading-tight text-zinc-400 dark:text-zinc-500">
                        {{ $previewText }}
                    </p>
                @endif
            @else
                <span class="text-[13px] text-zinc-500 dark:text-zinc-400">{{ $block['type'] }}</span>
            @endif
        </div>

        {{-- Actions — reveal on hover --}}
        <div class="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity duration-150 group-hover:opacity-100">
            <button
                type="button"
                wire:click="moveBlockUp('{{ $block['id'] }}', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})"
                class="flex h-7 w-7 items-center justify-center rounded-md text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-white/[0.07] dark:hover:text-zinc-300"
                title="{{ __('mksine::page_builder.move_up') }}" aria-label="{{ __('mksine::page_builder.move_up') }}"
            ><x-heroicon-o-chevron-up class="h-3.5 w-3.5" /></button>

            <button
                type="button"
                wire:click="moveBlockDown('{{ $block['id'] }}', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})"
                class="flex h-7 w-7 items-center justify-center rounded-md text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-white/[0.07] dark:hover:text-zinc-300"
                title="{{ __('mksine::page_builder.move_down') }}" aria-label="{{ __('mksine::page_builder.move_down') }}"
            ><x-heroicon-o-chevron-down class="h-3.5 w-3.5" /></button>

            <div class="mx-1 h-4 w-px bg-zinc-200 dark:bg-white/[0.08]"></div>

            <button
                type="button"
                wire:click="editBlock('{{ $block['id'] }}', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})"
                class="flex h-7 w-7 items-center justify-center rounded-md text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-white/[0.07] dark:hover:text-zinc-300"
                title="{{ __('mksine::page_builder.edit') }}" aria-label="{{ __('mksine::page_builder.edit') }}"
            ><x-heroicon-o-pencil-square class="h-3.5 w-3.5" /></button>

            <button
                type="button"
                wire:click="duplicateBlock('{{ $block['id'] }}', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})"
                class="flex h-7 w-7 items-center justify-center rounded-md text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-emerald-600 dark:text-zinc-500 dark:hover:bg-white/[0.07] dark:hover:text-emerald-400"
                title="{{ __('mksine::page_builder.duplicate') }}" aria-label="{{ __('mksine::page_builder.duplicate') }}"
            ><x-heroicon-o-document-duplicate class="h-3.5 w-3.5" /></button>

            <div class="relative inline-flex">
                <button
                    type="button"
                    @click="$wire.getBlockJsonForCopy('{{ $block['id'] }}', @js($parentId), @js($columnIndex)).then(() => { const j = $wire.copyBlockJson; if (!j) { window.dispatchEvent(new CustomEvent('copy-done', { detail: { blockId: '{{ $block['id'] }}', message: '{{ __('mksine::page_builder.copy_failed') }}', success: false } })); $wire.copyBlockJson = null; return; } const doCopy = () => { const ta = document.createElement('textarea'); ta.value = j; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); }; if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') { navigator.clipboard.writeText(j).catch(() => { doCopy(); }); } else { doCopy(); } window.dispatchEvent(new CustomEvent('copy-done', { detail: { blockId: '{{ $block['id'] }}', message: '{{ __('mksine::page_builder.copied') }}', success: true } })); $wire.copyBlockJson = null; })"
                    class="flex h-7 w-7 items-center justify-center rounded-md text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-white/[0.07] dark:hover:text-zinc-300"
                    title="{{ __('mksine::page_builder.copy_cmd') }}" aria-label="{{ __('mksine::page_builder.copy_cmd') }}"
                ><x-heroicon-o-clipboard-document class="h-3.5 w-3.5" /></button>
                <span
                    x-show="copyFeedback"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    x-text="copyFeedback"
                    class="absolute bottom-full left-1/2 z-50 mb-2 -translate-x-1/2 whitespace-nowrap rounded-md px-2 py-1 text-[11px] font-medium shadow-lg"
                    :class="copySuccess ? 'bg-emerald-600 text-white' : 'bg-red-500 text-white'"
                    style="display: none;"
                ></span>
            </div>

            <div class="mx-1 h-4 w-px bg-zinc-200 dark:bg-white/[0.08]"></div>

            <button
                type="button"
                wire:click="removeBlock('{{ $block['id'] }}', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})"
                wire:confirm="{{ __('mksine::page_builder.delete_confirm') }}"
                class="flex h-7 w-7 items-center justify-center rounded-md text-zinc-400 transition-colors hover:bg-red-50 hover:text-red-500 dark:text-zinc-500 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                title="{{ __('mksine::page_builder.delete') }}" aria-label="{{ __('mksine::page_builder.delete') }}"
            ><x-heroicon-o-trash class="h-3.5 w-3.5" /></button>
        </div>
    </div>

    {{-- Children (Columns / Grid) --}}
    @if($supportsChildren && isset($block['children']) && is_array($block['children']))
        <div class="border-t border-zinc-100 px-3 pb-3 pt-2.5 dark:border-white/[0.05]">
            <div class="grid gap-2.5" style="grid-template-columns: repeat({{ count($block['children']) }}, minmax(0, 1fr));">
                @foreach($block['children'] as $colIndex => $column)
                    <div
                        class="min-h-[100px] space-y-2 rounded-lg border border-dashed border-violet-200 bg-violet-50/30 p-2.5 dark:border-violet-500/25 dark:bg-violet-500/[0.04]"
                        data-sortable-column
                        data-parent-id="{{ $block['id'] }}"
                        data-column-index="{{ $colIndex }}"
                    >
                        <div class="flex items-center justify-between pb-1.5">
                            <span class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
                                {{ $componentClass ? $componentClass::getBuilderChildRegionLabel($colIndex, count($block['children'])) : __('mksine::page_builder.column').' '.($colIndex + 1) }}
                            </span>
                            <button
                                type="button"
                                wire:click="openComponentPanel(null, '{{ $block['id'] }}', {{ $colIndex }})"
                                class="flex h-5 w-5 items-center justify-center rounded text-zinc-400 transition-colors hover:bg-zinc-200 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-white/[0.08] dark:hover:text-zinc-300"
                                title="{{ __('mksine::page_builder.add_to_column') }}"
                            ><x-heroicon-o-plus class="h-3.5 w-3.5" /></button>
                        </div>

                        @foreach($column['items'] as $itemIndex => $item)
                            @include('mksine::page-builder.partials.block-item', [
                                'block' => $item,
                                'index' => $itemIndex,
                                'parentId' => $block['id'],
                                'columnIndex' => $colIndex,
                            ])
                        @endforeach

                        @if(empty($column['items']))
                            <div class="flex flex-col items-center justify-center gap-2 py-5 text-center">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-white/[0.05]">
                                    <x-heroicon-o-inbox class="h-5 w-5 text-zinc-400 dark:text-zinc-500" />
                                </div>
                                <p class="text-[11px] text-zinc-400 dark:text-zinc-500">{{ __('mksine::page_builder.empty_column') }}</p>
                                <button
                                    type="button"
                                    wire:click="openComponentPanel(0, '{{ $block['id'] }}', {{ $colIndex }})"
                                    class="text-[11px] font-medium text-violet-600 hover:underline dark:text-violet-400"
                                >+ {{ __('mksine::page_builder.add_component_short') }}</button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
