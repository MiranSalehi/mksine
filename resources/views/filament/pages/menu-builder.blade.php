<x-filament-panels::page>
    @if(!$selectedMenu)
        <div class="flex flex-col items-center justify-center py-20 text-center">
            <div class="relative mb-6">
                <div class="absolute inset-0 rounded-2xl bg-primary-500/10 blur-2xl"></div>
                <div class="relative flex h-24 w-24 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500/20 via-primary-500/20 to-violet-600/20 ring-1 ring-primary-500/20 dark:from-indigo-500/30 dark:to-violet-600/20">
                    <x-heroicon-o-bars-3-bottom-left class="h-12 w-12 text-primary-500 dark:text-primary-400" />
                </div>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                {{ __('mksine::menu_builder.no_menu_selected') }}
            </h3>
            <p class="mt-2 max-w-md text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                {{ __('mksine::menu_builder.select_menu_from_header') }}
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3" x-data="menuBuilder(@js($menuItems))">
            {{-- Left Panel: Item Sources --}}
            <div class="space-y-4 lg:col-span-1">
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-800/50 dark:ring-white/5">
                    <div class="flex items-center gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-500/10 dark:bg-primary-500/20">
                            <x-heroicon-o-plus-circle class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                        </div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ __('mksine::menu_builder.add_menu_items') }}
                        </h3>
                    </div>

                    <div class="space-y-3 p-4">
                        @foreach($sources as $key => $source)
                            <div x-data="{ expanded: false }" class="overflow-hidden rounded-xl border border-gray-200 transition-all duration-200 dark:border-gray-600/60 dark:hover:border-gray-500/60">
                                <button
                                    @click="expanded = !expanded; if(expanded) $wire.getSourceItems('{{ $key }}')"
                                    type="button"
                                    class="flex w-full items-center justify-between px-4 py-3 text-start transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                >
                                    <span class="flex items-center gap-3">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700/80">
                                            <x-dynamic-component :component="$source['icon']" class="h-4 w-4 text-gray-600 dark:text-gray-400" />
                                        </span>
                                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $source['label'] }}</span>
                                    </span>
                                    <x-heroicon-m-chevron-down class="h-5 w-5 shrink-0 text-gray-400 transition-transform duration-200" ::class="{ 'rotate-180': expanded }" />
                                </button>

                                <div x-show="expanded" x-collapse class="border-t border-gray-200 bg-gray-50/50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                                    @if($source['hasCustomForm'])
                                        <div class="space-y-3">
                                            <div>
                                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ __('mksine::menu_builder.url') }}
                                                </label>
                                                <input
                                                    type="url"
                                                    wire:model="sourceFormData.{{ $key }}.url"
                                                    placeholder="https://"
                                                    class="menu-builder-input w-full rounded-xl border-0 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm ring-1 ring-gray-200 transition-shadow placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-0 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:placeholder:text-gray-500 dark:focus:ring-offset-gray-900"
                                                />
                                            </div>
                                            <div>
                                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    {{ __('mksine::menu_builder.link_text') }}
                                                </label>
                                                <input
                                                    type="text"
                                                    wire:model="sourceFormData.{{ $key }}.label"
                                                    placeholder="{{ __('mksine::menu_builder.enter_link_text') }}"
                                                    class="menu-builder-input w-full rounded-xl border-0 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm ring-1 ring-gray-200 transition-shadow placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-0 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:placeholder:text-gray-500 dark:focus:ring-offset-gray-900"
                                                />
                                            </div>
                                        </div>
                                    @elseif($source['isListSource'] ?? false)
                                        <div class="space-y-3">
                                            <div class="relative">
                                                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                                                <input
                                                    type="search"
                                                    value="{{ $sourceSearch[$key] ?? '' }}"
                                                    placeholder="{{ __('mksine::menu_builder.search') }}"
                                                    class="menu-builder-input w-full rounded-xl border-0 bg-white py-2.5 ps-10 pe-4 text-sm text-gray-900 shadow-sm ring-1 ring-gray-200 transition-shadow placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-0 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:placeholder:text-gray-500 dark:focus:ring-offset-gray-900"
                                                    @input.debounce.300ms="$wire.setSourceSearch('{{ $key }}', $event.target.value)"
                                                />
                                            </div>
                                            @php $data = $sourceItems[$key] ?? null; @endphp
                                            @if($data)
                                                @php
                                                    $items = $data['items'];
                                                    $isNested = !empty($items) && array_key_exists('children', $items[0] ?? []);
                                                @endphp
                                                <div class="max-h-48 space-y-0.5 overflow-y-auto rounded-lg bg-white/50 p-1 dark:bg-black/20">
                                                    @if($isNested)
                                                        @foreach($items as $node)
                                                            @include('mksine::filament.pages.partials.menu-builder-source-item', [
                                                                'item' => $node,
                                                                'depth' => 0,
                                                                'sourceKey' => $key,
                                                            ])
                                                        @endforeach
                                                    @else
                                                        @php $treeItems = $this->getSourceItemsTreeOrder($data['items']); @endphp
                                                        @foreach($treeItems as $item)
                                                            @php $depth = (int) ($item['depth'] ?? 0); @endphp
                                                            <label class="flex cursor-pointer items-center gap-2.5 rounded-md py-1.5 pe-2 transition-colors hover:bg-gray-100 dark:hover:bg-gray-700/50" style="padding-inline-start: {{ $depth * 16 + 8 }}px;">
                                                                <input
                                                                    type="checkbox"
                                                                    wire:model="sourceFormData.{{ $key }}.selected"
                                                                    value="{{ $item['id'] }}"
                                                                    class="size-4 shrink-0 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                                                />
                                                                <span class="truncate text-sm text-gray-700 dark:text-gray-300">{{ $item['label'] }}</span>
                                                            </label>
                                                        @endforeach
                                                    @endif
                                                </div>
                                                @if($data['total'] > $data['per_page'])
                                                    @php
                                                        $cp = $data['current_page'];
                                                        $pp = $data['per_page'];
                                                        $total = $data['total'];
                                                        $lastPage = (int) max(1, ceil($total / $pp));
                                                        $from = $total > 0 ? ($cp - 1) * $pp + 1 : 0;
                                                        $to = min($cp * $pp, $total);
                                                    @endphp
                                                    <div class="flex items-center justify-between gap-3 border-t border-gray-200 pt-3 dark:border-gray-700">
                                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                            {{ $from }}–{{ $to }} {{ __('mksine::menu_builder.of') }} {{ $total }}
                                                        </span>
                                                        <div class="flex gap-1">
                                                            <button
                                                                type="button"
                                                                wire:click="setSourcePage('{{ $key }}', {{ $cp - 1 }})"
                                                                wire:loading.attr="disabled"
                                                                @disabled($cp <= 1)
                                                                class="inline-flex size-8 items-center justify-center rounded-lg bg-white text-gray-600 ring-1 ring-gray-200 transition-colors hover:bg-gray-50 hover:text-gray-900 disabled:pointer-events-none disabled:opacity-40 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10 dark:hover:bg-white/10 dark:hover:text-white"
                                                            >
                                                                <x-heroicon-o-chevron-left class="h-4 w-4" />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                wire:click="setSourcePage('{{ $key }}', {{ $cp + 1 }})"
                                                                wire:loading.attr="disabled"
                                                                @disabled($cp >= $lastPage)
                                                                class="inline-flex size-8 items-center justify-center rounded-lg bg-white text-gray-600 ring-1 ring-gray-200 transition-colors hover:bg-gray-50 hover:text-gray-900 disabled:pointer-events-none disabled:opacity-40 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10 dark:hover:bg-white/10 dark:hover:text-white"
                                                            >
                                                                <x-heroicon-o-chevron-right class="h-4 w-4" />
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif
                                            @else
                                                <div class="flex items-center justify-center py-8">
                                                    <span class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                                        <x-heroicon-o-arrow-path class="h-4 w-4 animate-spin" />
                                                        {{ __('mksine::menu_builder.loading') }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <p class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            {{ __('mksine::menu_builder.no_items_available') }}
                                        </p>
                                    @endif

                                    <button
                                        type="button"
                                        wire:click="addItemFromSource('{{ $key }}')"
                                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-primary-500"
                                    >
                                        <x-heroicon-m-plus class="h-4 w-4" />
                                        {{ __('mksine::menu_builder.add_to_menu') }}
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Right Panel: Menu Structure --}}
            <div class="lg:col-span-2">
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-800/50 dark:ring-white/5">
                    <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-500/10 dark:bg-primary-500/20">
                                <x-heroicon-o-bars-3 class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                    {{ __('mksine::menu_builder.menu_structure') }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('mksine::menu_builder.drag_to_reorder') }}
                                </p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700/80 dark:text-gray-400">
                            <x-heroicon-m-arrows-pointing-out class="h-3.5 w-3.5" />
                            {{ trans_choice('mksine::menu_builder.items', count($menuItems)) }}
                        </span>
                    </div>

                    <div class="min-h-[320px] p-5">
                        @if(count($menuItems) === 0)
                            <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 py-16 dark:border-gray-600">
                                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700/50">
                                    <x-heroicon-o-bars-3-bottom-left class="h-8 w-8 text-gray-400 dark:text-gray-500" />
                                </div>
                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('mksine::menu_builder.add_items_from_left') }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('mksine::menu_builder.drag_to_reorder') }}
                                </p>
                            </div>
                        @else
                            <div id="menu-items-container" class="space-y-1">
                                @include('mksine::filament.pages.partials.menu-item', ['items' => $menuItems, 'depth' => 0])
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('menuBuilder', (initialItems) => ({
                items: initialItems,

                init() {
                    this.$nextTick(() => {
                        this.initSortable();
                    });

                    Livewire.on('menu-items-updated', (data) => {
                        this.items = data.items;
                        this.$nextTick(() => this.initSortable());
                    });
                },

                initSortable() {
                    const isRtl = document.documentElement.dir === 'rtl' || document.documentElement.getAttribute('dir') === 'rtl';
                    const containers = document.querySelectorAll('.sortable-container');
                    containers.forEach(container => {
                        if (container._sortable) return;

                        container._sortable = new Sortable(container, {
                            group: 'menu-items',
                            animation: 150,
                            fallbackOnBody: true,
                            swapThreshold: 0.65,
                            invertSwap: isRtl,
                            emptyInsertThreshold: 30,
                            handle: '.drag-handle',
                            ghostClass: 'sortable-ghost',
                            chosenClass: 'sortable-chosen',
                            onStart: () => {
                                document.body.classList.add('dragging-menu-item');
                            },
                            onEnd: (evt) => {
                                document.body.classList.remove('dragging-menu-item');
                                this.updateStructure();
                            },
                        });
                    });
                },

                updateStructure() {
                    const newStructure = this.buildStructureFromDOM();
                    this.$wire.updateMenuStructure(newStructure);
                },

                buildStructureFromDOM(parent = null) {
                    let container = parent;
                    if (!container) {
                        const rootWrapper = document.getElementById('menu-items-container');
                        if (rootWrapper) {
                            container = rootWrapper.querySelector('.sortable-container');
                        }
                    }

                    const items = [];
                    if (!container) return items;

                    Array.from(container.children).forEach(wrapper => {
                        if (!wrapper.classList.contains('menu-item-wrapper')) return;

                        const id = parseInt(wrapper.dataset.itemId);
                        const childContainer = wrapper.querySelector('.sortable-container');
                        const children = childContainer ? this.buildStructureFromDOM(childContainer) : [];

                        items.push({
                            id: id,
                            children: children,
                        });
                    });

                    return items;
                },
            }));
        });
    </script>
    @endpush

    <style>
        .sortable-ghost {
            opacity: 0.4;
            background: rgb(var(--primary-500) / 0.15);
            border-radius: 0.75rem;
        }
        .sortable-chosen {
            background: rgb(var(--primary-500) / 0.08);
            border-radius: 0.75rem;
        }
        .menu-item-wrapper {
            transition: all 0.15s ease;
        }
        body.dragging-menu-item .sortable-container:empty {
            min-height: 52px;
            background-color: rgb(var(--primary-500) / 0.06);
            border: 2px dashed rgb(var(--primary-500) / 0.4);
            border-radius: 0.75rem;
            margin-top: 0;
            margin-bottom: 0.5rem;
        }
        .dark body.dragging-menu-item .sortable-container:empty {
            background-color: rgb(var(--primary-500) / 0.1);
            border-color: rgb(var(--primary-500) / 0.3);
        }
    </style>
</x-filament-panels::page>
