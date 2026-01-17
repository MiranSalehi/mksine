<x-filament-panels::page>
    @if(!$selectedMenu)
        <div class="flex items-center justify-center p-12">
            <div class="text-center">
                <x-heroicon-o-queue-list class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('No Menu Selected') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Select a menu from the header action to start building.') }}
                </p>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="menuBuilder(@js($menuItems))">
            {{-- Left Panel: Item Sources --}}
            <div class="lg:col-span-1 space-y-4">
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="fi-section-header flex items-center gap-3 px-6 py-4 border-b border-gray-200 dark:border-white/10">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                            {{ __('Add Menu Items') }}
                        </h3>
                    </div>

                    <div class="fi-section-content p-6 space-y-4">
                        @foreach($sources as $key => $source)
                            <div x-data="{ expanded: false }" class="border border-gray-200 dark:border-white/10 rounded-lg overflow-hidden">
                                <button
                                    @click="expanded = !expanded"
                                    type="button"
                                    class="flex items-center justify-between w-full px-4 py-3 text-left bg-gray-50 hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors"
                                >
                                    <span class="flex items-center gap-2">
                                        <x-dynamic-component :component="$source['icon']" class="w-5 h-5 text-gray-500" />
                                        <span class="font-medium text-gray-700 dark:text-gray-200">{{ $source['label'] }}</span>
                                    </span>
                                    <x-heroicon-m-chevron-down class="w-5 h-5 text-gray-400 transition-transform" ::class="{ 'rotate-180': expanded }" />
                                </button>

                                <div x-show="expanded" x-collapse class="p-4 bg-white dark:bg-gray-900">
                                    @if($source['hasCustomForm'])
                                        {{-- Custom Form (e.g., Custom Link) --}}
                                        <div class="space-y-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    {{ __('URL') }}
                                                </label>
                                                <input
                                                    type="url"
                                                    wire:model="sourceFormData.{{ $key }}.url"
                                                    placeholder="https://"
                                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                    {{ __('Link Text') }}
                                                </label>
                                                <input
                                                    type="text"
                                                    wire:model="sourceFormData.{{ $key }}.label"
                                                    placeholder="{{ __('Enter link text') }}"
                                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm"
                                                />
                                            </div>
                                        </div>
                                    @elseif(count($source['items']) > 0)
                                        {{-- Checkbox List --}}
                                        <div class="max-h-48 overflow-y-auto space-y-2">
                                            @foreach($source['items'] as $item)
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        wire:model="sourceFormData.{{ $key }}.selected"
                                                        value="{{ $item['id'] }}"
                                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                                    />
                                                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ $item['label'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-2">
                                            {{ __('No items available') }}
                                        </p>
                                    @endif

                                    <button
                                        type="button"
                                        wire:click="addItemFromSource('{{ $key }}')"
                                        class="mt-4 w-full inline-flex justify-center items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 rounded-lg transition-colors"
                                    >
                                        <x-heroicon-m-plus class="w-4 h-4" />
                                        {{ __('Add to Menu') }}
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Right Panel: Menu Structure --}}
            <div class="lg:col-span-2">
                <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="fi-section-header flex items-center justify-between gap-3 px-6 py-4 border-b border-gray-200 dark:border-white/10">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                            {{ __('Menu Structure') }}
                        </h3>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Drag items to reorder. Drag right to nest.') }}
                        </span>
                    </div>

                    <div class="fi-section-content p-6">
                        @if(count($menuItems) === 0)
                            <div class="flex items-center justify-center py-12 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
                                <div class="text-center">
                                    <x-heroicon-o-bars-3-bottom-left class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                                    <p class="text-gray-500 dark:text-gray-400">{{ __('Add items from the left panel') }}</p>
                                </div>
                            </div>
                        @else
                            {{-- Rendered items using recursion --}}
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
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('menuBuilder', (initialItems) => ({
                items: initialItems,

                init() {
                    this.$nextTick(() => {
                        this.initSortable();
                    });

                    // Listen for Livewire updates
                    Livewire.on('menu-items-updated', (data) => {
                        this.items = data.items;
                        this.$nextTick(() => this.initSortable());
                    });
                },

                initSortable() {
                    const containers = document.querySelectorAll('.sortable-container');
                    containers.forEach(container => {
                        if (container._sortable) return;

                        container._sortable = new Sortable(container, {
                            group: 'menu-items',
                            animation: 150,
                            fallbackOnBody: true,
                            swapThreshold: 0.65,
                            emptyInsertThreshold: 30, // Increased to make it easier to drop into empty (nested) lists
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
                    // For the root call, we need to get the first sortable-container inside the wrapper
                    let container = parent;
                    if (!container) {
                        const rootWrapper = document.getElementById('menu-items-container');
                        if (rootWrapper) {
                            container = rootWrapper.querySelector('.sortable-container');
                        }
                    }

                    const items = [];

                    if (!container) return items;

                    // Iterate over direct children wrappers
                    // We use children instead of querySelectorAll to respect :scope and avoid deep nesting issues if selector is loose
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
            background: rgb(var(--primary-500) / 0.1);
            border-radius: 0.5rem;
        }
        .sortable-chosen {
            background: rgb(var(--primary-500) / 0.05);
        }
        .menu-item-wrapper {
            transition: all 0.15s ease;
        }
        /* Highlight empty drop zones during drag for easier nesting */
        body.dragging-menu-item .sortable-container:empty {
            min-height: 48px;
            background-color: rgba(var(--gray-50), 0.5);
            border: 2px dashed rgb(var(--gray-300));
            border-radius: 0.5rem;
            margin-top: 0;
            margin-bottom: 0.5rem;
        }
        .dark body.dragging-menu-item .sortable-container:empty {
            background-color: rgba(var(--gray-800), 0.5);
            border-color: rgb(var(--gray-700));
        }
    </style>
</x-filament-panels::page>
