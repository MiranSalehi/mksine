@php
    $parentId = $parentId ?? null;
    $columnIndex = $columnIndex ?? null;
@endphp
<ul class="space-y-1" role="tree">
    @foreach ($blocks as $block)
        @php
            $blockId = (string) ($block['id'] ?? '');
            $info = $this->getBlockDisplayInfo($block);
            $name = $info['componentClass'] ? $info['componentClass']::getName() : ($block['type'] ?? '');
            $selected = $selectedBlockId === $blockId;
        @endphp
        <li
            role="treeitem"
            data-navigator-block="{{ $blockId }}"
            @if ($selected) data-selected="true" @endif
            @class([
                'rounded-md px-1 py-1',
                'bg-violet-50 dark:bg-violet-500/10' => $selected,
            ])
        >
            <div class="flex items-center gap-1">
                <button
                    type="button"
                    wire:click="selectBlock('{{ $blockId }}')"
                    class="min-w-0 flex-1 truncate rounded px-1 py-0.5 text-start text-xs font-medium text-zinc-800 dark:text-zinc-100"
                >
                    {{ $name }}
                </button>
                <button type="button" wire:click="moveNavigatorBlock('{{ $blockId }}', 'up', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})" class="rounded p-1 text-zinc-400 hover:bg-zinc-100" title="{{ __('mksine::page_builder.move_up') }}" aria-label="{{ __('mksine::page_builder.move_up') }}">
                    <x-heroicon-o-chevron-up class="h-3.5 w-3.5" />
                </button>
                <button type="button" wire:click="moveNavigatorBlock('{{ $blockId }}', 'down', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})" class="rounded p-1 text-zinc-400 hover:bg-zinc-100" title="{{ __('mksine::page_builder.move_down') }}" aria-label="{{ __('mksine::page_builder.move_down') }}">
                    <x-heroicon-o-chevron-down class="h-3.5 w-3.5" />
                </button>
                <button type="button" wire:click="editBlock('{{ $blockId }}', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})" class="rounded p-1 text-zinc-400 hover:bg-zinc-100" title="{{ __('mksine::page_builder.edit') }}" aria-label="{{ __('mksine::page_builder.edit') }}">
                    <x-heroicon-o-pencil-square class="h-3.5 w-3.5" />
                </button>
                @if (($block['type'] ?? '') === 'section_ref')
                    <button type="button" wire:click="unlinkSection('{{ $blockId }}')" class="rounded p-1 text-zinc-400 hover:bg-zinc-100" title="{{ __('mksine::page_builder.section_unlink') }}" aria-label="{{ __('mksine::page_builder.section_unlink') }}">
                        <x-heroicon-o-x-mark class="h-3.5 w-3.5" />
                    </button>
                @endif
                <button type="button" wire:click="removeBlock('{{ $blockId }}', {{ $parentId ? "'{$parentId}'" : 'null' }}, {{ $columnIndex ?? 'null' }})" class="rounded p-1 text-zinc-400 hover:bg-zinc-100" title="{{ __('mksine::page_builder.delete') }}" aria-label="{{ __('mksine::page_builder.delete') }}">
                    <x-heroicon-o-trash class="h-3.5 w-3.5" />
                </button>
            </div>
            @if (! empty($block['children']) && is_array($block['children']))
                @foreach ($block['children'] as $childIndex => $column)
                    <div class="ms-3 mt-1 border-s border-zinc-200 ps-2 dark:border-white/10">
                        <p class="px-1 text-[10px] uppercase tracking-wide text-zinc-400">{{ __('mksine::page_builder.navigator_column', ['number' => $childIndex + 1]) }}</p>
                        @include('mksine::page-builder.components.navigator-nodes', [
                            'blocks' => $column['items'] ?? [],
                            'parentId' => $blockId,
                            'columnIndex' => $childIndex,
                            'selectedBlockId' => $selectedBlockId,
                        ])
                    </div>
                @endforeach
            @endif
        </li>
    @endforeach
</ul>
