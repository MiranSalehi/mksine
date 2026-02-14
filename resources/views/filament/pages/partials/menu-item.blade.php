@props(['items', 'depth' => 0])

<div class="sortable-container space-y-0" style="margin-left: {{ $depth * 24 }}px;">
    @foreach($items as $item)
        <div
            wire:key="menu-item-{{ $item['id'] }}"
            class="menu-item-wrapper"
            data-item-id="{{ $item['id'] }}"
        >
            <div class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm hover:border-primary-400 dark:hover:border-primary-500 transition-all group">
                {{-- Drag Handle --}}
                <button type="button" class="drag-handle cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <x-heroicon-m-bars-2 class="w-5 h-5" />
                </button>

                {{-- Type Badge --}}
                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-md bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                    {{ ucfirst(str_replace('_', ' ', $item['type'])) }}
                </span>

                {{-- Label & Info --}}
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {{ $item['label'] }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate flex items-center gap-2">
                        @if($item['type'] === 'custom_link')
                            <span class="opacity-75">{{ $item['url'] }}</span>
                        @endif
                        <span class="opacity-50">&bull;</span>
                        <span class="opacity-75">
                            {{ $item['target'] === '_blank' ? __('New Tab') : __('Same Tab') }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    {{-- Edit Button --}}
                    <button
                        type="button"
                        wire:click="mountAction('editItem', { itemId: {{ $item['id'] }} })"
                        class="p-1 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400"
                        title="{{ __('Edit') }}"
                    >
                        <x-heroicon-m-pencil-square class="w-4 h-4" />
                    </button>

                    {{-- Delete Button --}}
                    <button
                        type="button"
                        wire:click="removeItem({{ $item['id'] }})"
                        wire:confirm="{{ __('Are you sure you want to remove this item?') }}"
                        class="p-1 text-gray-400 hover:text-danger-600 dark:hover:text-danger-400"
                        title="{{ __('Remove') }}"
                    >
                        <x-heroicon-m-trash class="w-4 h-4" />
                    </button>
                </div>
            </div>

            {{-- Children (recursive) --}}
            @if(!empty($item['children']))
                @include('mksine::filament.pages.partials.menu-item', [
                    'items' => $item['children'],
                    'depth' => $depth + 1
                ])
            @else
                {{-- Empty container for dropping --}}
                <div class="sortable-container" style="margin-left: 24px; min-height: 0;"></div>
            @endif
        </div>
    @endforeach
</div>
