@props(['items', 'depth' => 0])

{{--
    Flat-list emitter for menu items.

    Each row carries its current depth in `data-depth` and visualises it via `padding-inline-start`.
    Children are emitted in pre-order immediately after their parent. The root call iterates
    over all items at depth 0; the recursive include handles children.

    The DOM order of rows IS the source of truth for the tree; depth + sibling order encode
    parent/child relationships. The JS rebuilder in menu-builder.blade.php walks this flat list
    and reconstructs the {id, children} tree before sending it to Livewire.
--}}
@foreach($items as $item)
    <div
        wire:key="menu-row-{{ $item['id'] }}"
        class="menu-row"
        data-id="{{ $item['id'] }}"
        data-depth="{{ $depth }}"
        style="padding-inline-start: {{ $depth * 24 }}px;"
    >
        {{-- Depth guides (vertical lines for nested levels) --}}
        @if($depth > 0)
            @for($i = 1; $i <= $depth; $i++)
                <span
                    aria-hidden="true"
                    class="menu-row-guide"
                    style="inset-inline-start: {{ ($i - 1) * 24 + 11 }}px;"
                ></span>
            @endfor
        @endif

        <div class="menu-row-content group flex items-center gap-2.5 rounded-xl border border-zinc-200/80 bg-white px-3 py-2.5 shadow-[0_1px_3px_0_rgb(0_0_0/0.05)] transition-[border-color,box-shadow] duration-150 hover:border-violet-300 hover:shadow-[0_3px_10px_0_rgb(0_0_0/0.07)] dark:border-white/[0.07] dark:bg-zinc-900 dark:shadow-none dark:hover:border-violet-500/45">

            {{-- Drag handle --}}
            <button
                type="button"
                class="drag-handle shrink-0 cursor-grab rounded-md p-1 text-zinc-300 transition-colors hover:bg-zinc-100 hover:text-zinc-500 active:cursor-grabbing dark:text-zinc-600 dark:hover:bg-white/[0.06] dark:hover:text-zinc-400"
            >
                <x-heroicon-m-bars-2 class="h-4 w-4" />
            </button>

            {{-- Type badge --}}
            <span class="shrink-0 rounded-md border border-zinc-100 bg-zinc-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-zinc-400 dark:border-white/[0.06] dark:bg-white/[0.04] dark:text-zinc-500">
                {{ ucfirst(str_replace('_', ' ', $item['type'])) }}
            </span>

            {{-- Label & URL --}}
            <div class="min-w-0 flex-1">
                <p class="truncate text-[13px] font-semibold leading-tight text-zinc-900 dark:text-zinc-100">
                    {{ $item['label'] }}
                </p>
                <p class="mt-0.5 flex items-center gap-1.5 truncate text-[11px] leading-tight text-zinc-400 dark:text-zinc-500">
                    @if($item['type'] === 'custom_link' && !empty($item['url']))
                        <span class="truncate">{{ $item['url'] }}</span>
                        <span class="shrink-0">&bull;</span>
                    @endif
                    <span class="shrink-0">
                        {{ $item['target'] === '_blank' ? __('mksine::menu_builder.new_tab') : __('mksine::menu_builder.same_tab') }}
                    </span>
                </p>
            </div>

            {{-- Actions --}}
            <div class="flex shrink-0 items-center gap-0.5 opacity-0 transition-opacity duration-150 group-hover:opacity-100">

                @if($depth > 0)
                    <button
                        type="button"
                        wire:click="outdentItem({{ $item['id'] }})"
                        class="flex h-7 items-center gap-1 rounded-md px-1.5 text-zinc-400 transition-colors hover:bg-violet-50 hover:text-violet-600 dark:text-zinc-500 dark:hover:bg-violet-500/10 dark:hover:text-violet-400"
                        title="{{ __('mksine::menu_builder.outdent') }}"
                    >
                        <x-heroicon-m-arrow-uturn-left class="h-3.5 w-3.5" />
                        <span class="text-[10px] font-semibold leading-none">{{ __('mksine::menu_builder.outdent_short') }}</span>
                    </button>
                @endif

                <button
                    type="button"
                    wire:click="indentItem({{ $item['id'] }})"
                    class="flex h-7 items-center gap-1 rounded-md px-1.5 text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-white/[0.07] dark:hover:text-zinc-300"
                    title="{{ __('mksine::menu_builder.indent') }}"
                >
                    <x-heroicon-m-arrow-down-right class="h-3.5 w-3.5" />
                    <span class="text-[10px] font-semibold leading-none">{{ __('mksine::menu_builder.indent_short') }}</span>
                </button>

                <div class="mx-0.5 h-4 w-px bg-zinc-200 dark:bg-white/[0.08]"></div>

                <button
                    type="button"
                    wire:click="mountAction('editItem', { itemId: {{ $item['id'] }} })"
                    class="flex h-7 w-7 items-center justify-center rounded-md text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-white/[0.07] dark:hover:text-zinc-300"
                    title="{{ __('mksine::menu_builder.edit') }}"
                ><x-heroicon-m-pencil-square class="h-3.5 w-3.5" /></button>

                <button
                    type="button"
                    wire:click="removeItem({{ $item['id'] }})"
                    wire:confirm="{{ __('mksine::menu_builder.remove_item_confirm') }}"
                    class="flex h-7 w-7 items-center justify-center rounded-md text-zinc-400 transition-colors hover:bg-red-50 hover:text-red-500 dark:text-zinc-500 dark:hover:bg-red-500/10 dark:hover:text-red-400"
                    title="{{ __('mksine::menu_builder.remove') }}"
                ><x-heroicon-m-trash class="h-3.5 w-3.5" /></button>
            </div>
        </div>
    </div>

    @if(!empty($item['children']))
        @include('mksine::filament.pages.partials.menu-item', [
            'items' => $item['children'],
            'depth' => $depth + 1,
        ])
    @endif
@endforeach
