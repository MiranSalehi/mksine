@props(['items', 'depth' => 0])

<div class="sortable-container space-y-1" style="margin-inline-start: {{ $depth * 20 }}px;">
    @foreach($items as $item)
        <div
            wire:key="menu-item-{{ $item['id'] }}"
            class="menu-item-wrapper"
            data-item-id="{{ $item['id'] }}"
        >
            <div class="group flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 shadow-sm ring-1 ring-black/5 transition-all hover:border-gray-300 hover:shadow-md dark:border-gray-600/60 dark:bg-gray-800/50 dark:ring-white/5 dark:hover:border-gray-500/80">
                {{-- Drag Handle --}}
                <button type="button" class="drag-handle shrink-0 cursor-grab text-gray-400 transition-colors hover:text-gray-600 active:cursor-grabbing dark:hover:text-gray-300">
                    <x-heroicon-m-bars-2 class="h-5 w-5" />
                </button>

                {{-- Type Badge --}}
                <span class="shrink-0 rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700/80 dark:text-gray-400">
                    {{ ucfirst(str_replace('_', ' ', $item['type'])) }}
                </span>

                {{-- Label & Info --}}
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-medium text-gray-900 dark:text-white">
                        {{ $item['label'] }}
                    </div>
                    <div class="mt-0.5 flex items-center gap-2 truncate text-xs text-gray-500 dark:text-gray-400">
                        @if($item['type'] === 'custom_link' && !empty($item['url']))
                            <span class="truncate opacity-80">{{ $item['url'] }}</span>
                            <span class="shrink-0 opacity-50">&bull;</span>
                        @endif
                        <span class="shrink-0 opacity-80">
                            {{ $item['target'] === '_blank' ? __('mksine::menu_builder.new_tab') : __('mksine::menu_builder.same_tab') }}
                        </span>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity group-hover:opacity-100">
                    <button
                        type="button"
                        wire:click="mountAction('editItem', { itemId: {{ $item['id'] }} })"
                        class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-primary-50 hover:text-primary-600 dark:hover:bg-primary-500/10 dark:hover:text-primary-400"
                        title="{{ __('mksine::menu_builder.edit') }}"
                    >
                        <x-heroicon-m-pencil-square class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        wire:click="removeItem({{ $item['id'] }})"
                        wire:confirm="{{ __('mksine::menu_builder.remove_item_confirm') }}"
                        class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-danger-50 hover:text-danger-600 dark:hover:bg-danger-500/10 dark:hover:text-danger-400"
                        title="{{ __('mksine::menu_builder.remove') }}"
                    >
                        <x-heroicon-m-trash class="h-4 w-4" />
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
                <div class="sortable-container" style="margin-inline-start: 20px; min-height: 0;"></div>
            @endif
        </div>
    @endforeach
</div>
