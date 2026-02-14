@php
    $registry = app(\Miran\Mksine\Core\PageBuilder\ComponentRegistry::class);
    $componentClass = $registry->get($block['type']);
    $supportsChildren = $componentClass ? $componentClass::supportsChildren() : false;
@endphp

<div
    class="group relative bg-white dark:bg-gray-800 rounded-xl border-2 border-gray-200 dark:border-gray-700 hover:border-pink-300 dark:hover:border-pink-700 transition-all shadow-sm hover:shadow-md"
    data-block-id="{{ $block['id'] }}"
    x-data="{ copyFeedback: '', copySuccess: false }"
    @copy-done.window="if ($event.detail?.blockId === '{{ $block['id'] }}') { copyFeedback = $event.detail.message; copySuccess = $event.detail.success !== false; setTimeout(() => { copyFeedback = ''; copySuccess = false }, 2200) }"
>
    {{-- Block Header --}}
    <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-700">
        {{-- Drag Handle --}}
        <div data-sortable-handle class="cursor-grab active:cursor-grabbing p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <x-heroicon-o-bars-3 class="w-5 h-5" />
        </div>

        {{-- Component Icon & Name --}}
        <div class="flex items-center gap-2 flex-1 min-w-0">
            @if($componentClass)
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-pink-100 to-purple-100 dark:from-pink-900/30 dark:to-purple-900/30 flex items-center justify-center shrink-0">
                    <x-dynamic-component :component="$componentClass::getIcon()" class="w-4 h-4 text-pink-600 dark:text-pink-400" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200 truncate">{{ $componentClass::getName() }}</p>
                    @if($block['type'] === 'heading' && !empty($block['data']['text']))
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ \Illuminate\Support\Str::limit($block['data']['text'], 40) }}</p>
                    @elseif($block['type'] === 'text' && !empty($block['data']['content']))
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ \Illuminate\Support\Str::limit(strip_tags($block['data']['content']), 40) }}</p>
                    @elseif($block['type'] === 'button' && !empty($block['data']['text']))
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $block['data']['text'] }}</p>
                    @endif
                </div>
            @else
                <span class="text-sm text-gray-500">{{ $block['type'] }}</span>
            @endif
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            {{-- Move Up --}}
            <button
                type="button"
                wire:click="moveBlockUp('{{ $block['id'] }}', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})"
                class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
                title="{{ __('Move Up') }}"
            >
                <x-heroicon-o-chevron-up class="w-4 h-4" />
            </button>

            {{-- Move Down --}}
            <button
                type="button"
                wire:click="moveBlockDown('{{ $block['id'] }}', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})"
                class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded transition-colors"
                title="{{ __('Move Down') }}"
            >
                <x-heroicon-o-chevron-down class="w-4 h-4" />
            </button>

            <div class="w-px h-4 bg-gray-200 dark:bg-gray-700 mx-1"></div>

            {{-- Edit --}}
            <button
                type="button"
                wire:click="editBlock('{{ $block['id'] }}', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})"
                class="p-1.5 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded transition-colors"
                title="{{ __('Edit') }}"
            >
                <x-heroicon-o-pencil-square class="w-4 h-4" />
            </button>

            {{-- Duplicate --}}
            <button
                type="button"
                wire:click="duplicateBlock('{{ $block['id'] }}', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})"
                class="p-1.5 text-gray-400 hover:text-green-600 dark:hover:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded transition-colors"
                title="{{ __('Duplicate') }}"
            >
                <x-heroicon-o-document-duplicate class="w-4 h-4" />
            </button>

            {{-- Copy --}}
            <div class="relative inline-flex">
                <button
                    type="button"
                    @click="$wire.getBlockJsonForCopy('{{ $block['id'] }}', @js($parentId), @js($columnIndex)).then(() => { const j = $wire.copyBlockJson; if (!j) { window.dispatchEvent(new CustomEvent('copy-done', { detail: { blockId: '{{ $block['id'] }}', message: '{{ __('Copy failed') }}', success: false } })); $wire.copyBlockJson = null; return; } const doCopy = () => { const ta = document.createElement('textarea'); ta.value = j; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); }; if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') { navigator.clipboard.writeText(j).catch(() => { doCopy(); }); } else { doCopy(); } window.dispatchEvent(new CustomEvent('copy-done', { detail: { blockId: '{{ $block['id'] }}', message: '{{ __('Copied!') }}', success: true } })); $wire.copyBlockJson = null; })"
                    class="p-1.5 text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded transition-colors"
                    title="{{ __('Copy (Cmd+C)') }}"
                >
                    <x-heroicon-o-clipboard-document class="w-4 h-4" />
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
                    class="absolute left-1/2 -translate-x-1/2 bottom-full mb-1 px-2 py-1 text-xs font-medium rounded-md whitespace-nowrap shadow-lg z-20"
                    :class="copySuccess ? 'bg-green-600 text-white' : 'bg-red-500 text-white'"
                    style="display: none;"
                ></span>
            </div>

            {{-- Delete --}}
            <button
                type="button"
                wire:click="removeBlock('{{ $block['id'] }}', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})"
                wire:confirm="{{ __('Are you sure you want to delete this component?') }}"
                class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition-colors"
                title="{{ __('Delete') }}"
            >
                <x-heroicon-o-trash class="w-4 h-4" />
            </button>
        </div>
    </div>

    {{-- Children (for Columns) --}}
    @if($supportsChildren && isset($block['children']) && is_array($block['children']))
        <div class="p-4">
            <div class="grid gap-4" style="grid-template-columns: repeat({{ count($block['children']) }}, minmax(0, 1fr));">
                @foreach($block['children'] as $colIndex => $column)
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg border-2 border-dashed border-gray-200 dark:border-gray-700 min-h-[120px] p-3">
                        {{-- Column Header --}}
                        <div class="flex items-center justify-between mb-2 pb-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Column') }} {{ $colIndex + 1 }}</span>
                            <button
                                type="button"
                                wire:click="openComponentPanel(null, '{{ $block['id'] }}', {{ $colIndex }})"
                                class="p-1 text-gray-400 hover:text-pink-500 transition-colors"
                                title="{{ __('Add to column') }}"
                            >
                                <x-heroicon-o-plus class="w-4 h-4" />
                            </button>
                        </div>

                        {{-- Column Items --}}
                        @if(!empty($column['items']))
                            <div class="space-y-2" data-sortable-column data-parent-id="{{ $block['id'] }}" data-column-index="{{ $colIndex }}">
                                @foreach($column['items'] as $itemIndex => $item)
                                    @include('mksine::page-builder.partials.block-item', [
                                        'block' => $item,
                                        'index' => $itemIndex,
                                        'parentId' => $block['id'],
                                        'columnIndex' => $colIndex,
                                    ])
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center text-center py-4">
                                <x-heroicon-o-inbox class="w-8 h-8 text-gray-300 dark:text-gray-600 mb-2" />
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ __('Empty column') }}</p>
                                <button
                                    type="button"
                                    wire:click="openComponentPanel(0, '{{ $block['id'] }}', {{ $colIndex }})"
                                    class="mt-2 text-xs text-pink-500 hover:text-pink-600 font-medium"
                                >
                                    + {{ __('Add component') }}
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
