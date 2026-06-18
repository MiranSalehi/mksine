<x-filament-panels::page>
    @if(!$selectedMenu)
        {{-- No menu selected state --}}
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 ring-1 ring-violet-100 dark:bg-violet-500/10 dark:text-violet-400 dark:ring-violet-500/20">
                <x-heroicon-o-bars-3-bottom-left class="h-7 w-7" />
            </div>
            <h3 class="mb-1.5 text-[15px] font-semibold tracking-tight text-zinc-900 dark:text-white">
                {{ __('mksine::menu_builder.no_menu_selected') }}
            </h3>
            <p class="max-w-xs text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
                {{ __('mksine::menu_builder.select_menu_from_header') }}
            </p>
        </div>
    @else
        <div class="mksine-menu-builder-root grid grid-cols-1 gap-5 lg:grid-cols-3" x-data="menuBuilder(@js($menuItems))">

            {{-- ── Left panel: item sources ── --}}
            <div class="space-y-3 lg:col-span-1">
                <div class="mksine-menu-panel overflow-hidden rounded-xl border border-zinc-200/80 bg-white shadow-[0_1px_3px_0_rgb(0_0_0/0.06)] dark:border-white/[0.07] dark:bg-zinc-900">

                    {{-- Panel header --}}
                    <div class="flex items-center gap-3 border-b border-zinc-100 px-4 py-3.5 dark:border-white/[0.06]">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[8px] bg-violet-50 text-violet-600 ring-1 ring-violet-100 dark:bg-violet-500/10 dark:text-violet-400 dark:ring-violet-500/20">
                            <x-heroicon-o-plus-circle class="h-4 w-4" />
                        </div>
                        <h3 class="text-[13px] font-semibold text-zinc-900 dark:text-white">
                            {{ __('mksine::menu_builder.add_menu_items') }}
                        </h3>
                    </div>

                    {{-- Source accordions --}}
                    <div class="space-y-2 p-3">
                        @foreach($sources as $key => $source)
                            <div
                                x-data="{ expanded: false }"
                                class="overflow-hidden rounded-xl border border-zinc-200/80 transition-colors duration-150 dark:border-white/[0.07]"
                                :class="expanded ? 'border-violet-200 dark:border-violet-500/30' : ''"
                            >
                                {{-- Accordion trigger --}}
                                <button
                                    @click="expanded = !expanded; if(expanded) $wire.getSourceItems('{{ $key }}')"
                                    type="button"
                                    class="flex w-full items-center justify-between px-3.5 py-3 text-start transition-colors hover:bg-zinc-50 dark:hover:bg-white/[0.04]"
                                    :class="expanded ? 'bg-zinc-50 dark:bg-white/[0.03]' : ''"
                                >
                                    <span class="flex items-center gap-2.5">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-white/[0.07] dark:text-zinc-400"
                                            :class="expanded ? 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400' : ''"
                                        >
                                            <x-dynamic-component :component="$source['icon']" class="h-3.5 w-3.5" />
                                        </span>
                                        <span class="text-[13px] font-semibold text-zinc-800 dark:text-zinc-200">{{ $source['label'] }}</span>
                                    </span>
                                    <x-heroicon-m-chevron-down
                                        class="h-4 w-4 shrink-0 text-zinc-400 transition-transform duration-200 dark:text-zinc-500"
                                        ::class="{ 'rotate-180': expanded }"
                                    />
                                </button>

                                {{-- Accordion content --}}
                                <div x-show="expanded" x-collapse class="border-t border-zinc-100 bg-zinc-50/50 p-3.5 dark:border-white/[0.06] dark:bg-white/[0.02]">
                                    @if($source['hasCustomForm'])
                                        <div class="space-y-2.5">
                                            <div>
                                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                                    {{ __('mksine::menu_builder.url') }}
                                                </label>
                                                <input
                                                    type="url"
                                                    wire:model="sourceFormData.{{ $key }}.url"
                                                    placeholder="https://"
                                                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 shadow-none outline-none placeholder:text-zinc-400 transition focus:border-violet-400 focus:ring-2 focus:ring-violet-400/20 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-white dark:placeholder:text-zinc-500"
                                                />
                                            </div>
                                            <div>
                                                <label class="mb-1.5 block text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                                    {{ __('mksine::menu_builder.link_text') }}
                                                </label>
                                                <input
                                                    type="text"
                                                    wire:model="sourceFormData.{{ $key }}.label"
                                                    placeholder="{{ __('mksine::menu_builder.enter_link_text') }}"
                                                    class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900 outline-none placeholder:text-zinc-400 transition focus:border-violet-400 focus:ring-2 focus:ring-violet-400/20 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-white dark:placeholder:text-zinc-500"
                                                />
                                            </div>
                                        </div>
                                    @elseif($source['isListSource'] ?? false)
                                        <div class="space-y-2.5">
                                            {{-- Search --}}
                                            <div class="relative">
                                                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute start-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-zinc-400" />
                                                <input
                                                    type="search"
                                                    value="{{ $sourceSearch[$key] ?? '' }}"
                                                    placeholder="{{ __('mksine::menu_builder.search') }}"
                                                    class="w-full rounded-lg border border-zinc-200 bg-white py-2 ps-9 pe-3 text-sm text-zinc-900 outline-none placeholder:text-zinc-400 transition focus:border-violet-400 focus:ring-2 focus:ring-violet-400/20 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-white dark:placeholder:text-zinc-500"
                                                    @input.debounce.300ms="$wire.setSourceSearch('{{ $key }}', $event.target.value)"
                                                />
                                            </div>

                                            @php $data = $sourceItems[$key] ?? null; @endphp
                                            @if($data)
                                                @php
                                                    $items    = $data['items'];
                                                    $isNested = !empty($items) && array_key_exists('children', $items[0] ?? []);
                                                @endphp
                                                <div class="max-h-48 space-y-0.5 overflow-y-auto rounded-lg border border-zinc-200/60 bg-white p-1 dark:border-white/[0.06] dark:bg-zinc-950/40">
                                                    @if($isNested)
                                                        @foreach($items as $node)
                                                            @include('mksine::filament.pages.partials.menu-builder-source-item', [
                                                                'item'      => $node,
                                                                'depth'     => 0,
                                                                'sourceKey' => $key,
                                                            ])
                                                        @endforeach
                                                    @else
                                                        @php $treeItems = $this->getSourceItemsTreeOrder($data['items']); @endphp
                                                        @foreach($treeItems as $item)
                                                            @php $depth = (int)($item['depth'] ?? 0); @endphp
                                                            <label
                                                                class="flex cursor-pointer items-center gap-2.5 rounded-md py-1.5 pe-2 transition-colors hover:bg-zinc-50 dark:hover:bg-white/[0.05]"
                                                                style="padding-inline-start: {{ $depth * 16 + 8 }}px;"
                                                            >
                                                                <input
                                                                    type="checkbox"
                                                                    wire:model="sourceFormData.{{ $key }}.selected"
                                                                    value="{{ $item['id'] }}"
                                                                    class="size-4 shrink-0 rounded border-zinc-300 text-violet-600 focus:ring-violet-500 dark:border-zinc-600"
                                                                />
                                                                <span class="truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $item['label'] }}</span>
                                                            </label>
                                                        @endforeach
                                                    @endif
                                                </div>

                                                {{-- Pagination --}}
                                                @if($data['total'] > $data['per_page'])
                                                    @php
                                                        $cp       = $data['current_page'];
                                                        $pp       = $data['per_page'];
                                                        $total    = $data['total'];
                                                        $lastPage = (int) max(1, ceil($total / $pp));
                                                        $from     = $total > 0 ? ($cp - 1) * $pp + 1 : 0;
                                                        $to       = min($cp * $pp, $total);
                                                    @endphp
                                                    <div class="flex items-center justify-between gap-3 border-t border-zinc-100 pt-2.5 dark:border-white/[0.06]">
                                                        <span class="text-[11px] text-zinc-400 dark:text-zinc-500">
                                                            {{ $from }}–{{ $to }} {{ __('mksine::menu_builder.of') }} {{ $total }}
                                                        </span>
                                                        <div class="flex gap-1">
                                                            <button
                                                                type="button"
                                                                wire:click="setSourcePage('{{ $key }}', {{ $cp - 1 }})"
                                                                wire:loading.attr="disabled"
                                                                @disabled($cp <= 1)
                                                                class="flex h-7 w-7 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-500 transition-colors hover:bg-zinc-50 hover:text-zinc-800 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-zinc-400 dark:hover:bg-white/[0.07]"
                                                            ><x-heroicon-o-chevron-left class="h-3.5 w-3.5" /></button>
                                                            <button
                                                                type="button"
                                                                wire:click="setSourcePage('{{ $key }}', {{ $cp + 1 }})"
                                                                wire:loading.attr="disabled"
                                                                @disabled($cp >= $lastPage)
                                                                class="flex h-7 w-7 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-500 transition-colors hover:bg-zinc-50 hover:text-zinc-800 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-zinc-400 dark:hover:bg-white/[0.07]"
                                                            ><x-heroicon-o-chevron-right class="h-3.5 w-3.5" /></button>
                                                        </div>
                                                    </div>
                                                @endif
                                            @else
                                                <div class="flex items-center justify-center py-7">
                                                    <span class="inline-flex items-center gap-2 text-xs text-zinc-400 dark:text-zinc-500">
                                                        <x-heroicon-o-arrow-path class="h-3.5 w-3.5 animate-spin" />
                                                        {{ __('mksine::menu_builder.loading') }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <p class="py-5 text-center text-xs text-zinc-400 dark:text-zinc-500">
                                            {{ __('mksine::menu_builder.no_items_available') }}
                                        </p>
                                    @endif

                                    {{-- Add to menu CTA --}}
                                    <button
                                        type="button"
                                        wire:click="addItemFromSource('{{ $key }}')"
                                        class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg bg-violet-600 px-4 py-2.5 text-xs font-semibold text-white shadow-[0_2px_6px_0_rgb(124_58_237/0.28)] transition-colors hover:bg-violet-700"
                                    >
                                        <x-heroicon-m-plus class="h-3.5 w-3.5" />
                                        {{ __('mksine::menu_builder.add_to_menu') }}
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ── Right panel: menu structure ── --}}
            <div class="lg:col-span-2">
                <div class="mksine-menu-panel overflow-hidden rounded-xl border border-zinc-200/80 bg-white shadow-[0_1px_3px_0_rgb(0_0_0/0.06)] dark:border-white/[0.07] dark:bg-zinc-900">

                    {{-- Panel header --}}
                    <div class="flex items-center justify-between gap-4 border-b border-zinc-100 px-4 py-3.5 dark:border-white/[0.06]">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-[8px] bg-violet-50 text-violet-600 ring-1 ring-violet-100 dark:bg-violet-500/10 dark:text-violet-400 dark:ring-violet-500/20">
                                <x-heroicon-o-bars-3 class="h-4 w-4" />
                            </div>
                            <div>
                                <h3 class="text-[13px] font-semibold text-zinc-900 dark:text-white">
                                    {{ __('mksine::menu_builder.menu_structure') }}
                                </h3>
                                <p class="text-[11px] text-zinc-400 dark:text-zinc-500">
                                    {{ __('mksine::menu_builder.drag_to_reorder') }}
                                </p>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200/80 bg-zinc-50 px-2.5 py-1 text-[11px] font-semibold text-zinc-500 dark:border-white/[0.07] dark:bg-white/[0.04] dark:text-zinc-400">
                            {{ trans_choice('mksine::menu_builder.items', count($menuItems)) }}
                        </span>
                    </div>

                    {{-- Menu items list --}}
                    <div class="min-h-[320px] p-4">
                        @if(count($menuItems) === 0)
                            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-violet-200 bg-violet-50/20 py-16 text-center dark:border-violet-500/25 dark:bg-violet-500/[0.03]">
                                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-50 text-violet-500 ring-1 ring-violet-100 dark:bg-violet-500/10 dark:text-violet-400 dark:ring-violet-500/20">
                                    <x-heroicon-o-bars-3-bottom-left class="h-6 w-6" />
                                </div>
                                <p class="text-[13px] font-semibold text-zinc-700 dark:text-zinc-300">
                                    {{ __('mksine::menu_builder.add_items_from_left') }}
                                </p>
                                <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                                    {{ __('mksine::menu_builder.drag_to_reorder') }}
                                </p>
                            </div>
                        @else
                            <div id="menu-items-container">
                                <div class="sortable-flat space-y-1.5">
                                    @include('mksine::filament.pages.partials.menu-item', ['items' => $menuItems, 'depth' => 0])
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    @push('scripts')
    <script>
        /*
         * Flat-list nested-sortable for the Menu Builder.
         *
         * Design rationale:
         *   Nested SortableJS containers cannot reliably support drag-outdenting because
         *   mouse hit-testing always picks the deepest container under the cursor — there
         *   is no clean way to "exit" a sub-list while dragging. We therefore use a single
         *   flat list. Indentation is purely visual (padding-inline-start) and the depth
         *   each row claims is recorded in `data-depth`. The active depth is recomputed
         *   continuously from the horizontal mouse delta during a drag, then clamped to
         *   the only set of depths that yield a structurally valid tree given the new
         *   sibling neighbours. On drop we walk the flat DOM and rebuild the tree before
         *   sending it to Livewire.
         *
         *   When an item with descendants is dragged, the descendants are detached from
         *   the DOM at drag-start and re-attached immediately after the dragged row at
         *   drag-end with their relative depths preserved (shifted by the new base depth).
         *   This keeps SortableJS's view of the list simple — it only ever moves the
         *   single dragged row.
         */
        const MENU_INDENT_PX = 24;
        const MENU_MAX_DEPTH = 4;
        // Hysteresis: require this much horizontal travel beyond a depth boundary
        // before we change the active depth — prevents jitter on shaky cursors.
        const MENU_DEPTH_HYSTERESIS_PX = 8;

        /**
         * Read clientX from a mouse, pointer, drag, or touch event uniformly.
         * Returns null if no clientX could be determined.
         */
        function readClientX(e) {
            if (typeof e.clientX === 'number' && !Number.isNaN(e.clientX) && e.clientX !== 0) {
                return e.clientX;
            }
            if (e.touches && e.touches[0]) {
                return e.touches[0].clientX;
            }
            if (e.changedTouches && e.changedTouches[0]) {
                return e.changedTouches[0].clientX;
            }
            return typeof e.clientX === 'number' ? e.clientX : null;
        }

        document.addEventListener('alpine:init', () => {
            Alpine.data('menuBuilder', (initialItems) => ({
                items: initialItems,

                // Drag state
                draggedRow: null,
                dragStartMouseX: 0,
                dragStartDepth: 0,
                descendantRows: [],
                lastMouseX: 0,
                isRtl: false,
                _sortableInstance: null,
                _trackHandler: null,
                _livewireHandler: null,

                init() {
                    this.isRtl = document.documentElement.dir === 'rtl'
                        || document.documentElement.getAttribute('dir') === 'rtl';

                    // Single tracker for both mouse and touch positions. Used while
                    // dragging to compute horizontal delta → target depth.
                    this._trackHandler = (e) => {
                        const x = readClientX(e);
                        if (x !== null) {
                            this.lastMouseX = x;
                            if (this.draggedRow) {
                                this.updateDepthFromMouse();
                            }
                        }
                    };
                    document.addEventListener('mousemove', this._trackHandler, { passive: true, capture: true });
                    document.addEventListener('touchmove', this._trackHandler, { passive: true, capture: true });
                    document.addEventListener('pointermove', this._trackHandler, { passive: true, capture: true });
                    // Also capture position at pointer/touch/mouse-down — the user may
                    // start a drag without immediate horizontal movement, and we need
                    // dragStartMouseX to reflect the actual press position.
                    document.addEventListener('mousedown', this._trackHandler, { passive: true, capture: true });
                    document.addEventListener('touchstart', this._trackHandler, { passive: true, capture: true });
                    document.addEventListener('pointerdown', this._trackHandler, { passive: true, capture: true });

                    this.$nextTick(() => this.initSortable());

                    Livewire.on('menu-items-updated', (data) => {
                        this.items = data.items;
                        this.$nextTick(() => this.reinitSortable());
                    });

                    this._livewireHandler = () => this.$nextTick(() => this.reinitSortable());
                    document.addEventListener('livewire:updated', this._livewireHandler);
                },

                destroy() {
                    if (this._trackHandler) {
                        document.removeEventListener('mousemove', this._trackHandler, { capture: true });
                        document.removeEventListener('touchmove', this._trackHandler, { capture: true });
                        document.removeEventListener('pointermove', this._trackHandler, { capture: true });
                        document.removeEventListener('mousedown', this._trackHandler, { capture: true });
                        document.removeEventListener('touchstart', this._trackHandler, { capture: true });
                        document.removeEventListener('pointerdown', this._trackHandler, { capture: true });
                    }
                    if (this._livewireHandler) {
                        document.removeEventListener('livewire:updated', this._livewireHandler);
                    }
                    if (this._sortableInstance) {
                        try { this._sortableInstance.destroy(); } catch (_) {}
                        this._sortableInstance = null;
                    }
                },

                getContainer() {
                    const root = document.getElementById('menu-items-container');
                    return root ? root.querySelector('.sortable-flat') : null;
                },

                getAllRows() {
                    const c = this.getContainer();
                    return c ? Array.from(c.querySelectorAll(':scope > .menu-row')) : [];
                },

                reinitSortable() {
                    if (this._sortableInstance) {
                        try { this._sortableInstance.destroy(); } catch (_) {}
                        this._sortableInstance = null;
                    }
                    this.initSortable();
                },

                initSortable() {
                    const container = this.getContainer();
                    if (!container || this._sortableInstance) return;

                    this._sortableInstance = new Sortable(container, {
                        animation: 130,
                        handle: '.drag-handle',
                        draggable: '.menu-row',
                        ghostClass: 'sortable-ghost',
                        chosenClass: 'sortable-chosen',
                        // CRITICAL: use SortableJS's mouse-based fallback rather than the
                        // browser's HTML5 drag API. The native API suppresses `mousemove`
                        // events on document during a drag, which would break our
                        // horizontal-depth tracking. The fallback path uses pointer/mouse
                        // events that fire normally.
                        forceFallback: true,
                        fallbackTolerance: 4,
                        // Make the followed clone visually similar to the source row so
                        // the user can clearly see depth changes on the ghost.
                        fallbackClass: 'menu-row-fallback',
                        // Ignore descendants of the currently-dragged row so SortableJS
                        // does not try to use them as drop targets. This is what stops
                        // the dragged subtree from being broken apart visually.
                        filter: '.menu-row--descendant-of-drag',
                        preventOnFilter: false,
                        onStart: (evt) => this.onDragStart(evt),
                        onChange: () => {
                            this.enforceDepthBounds();
                            this.snapDescendantsToParent();
                        },
                        onEnd: (evt) => this.onDragEnd(evt),
                    });
                },

                onDragStart(evt) {
                    this.draggedRow = evt.item;

                    // Capture cursor X at the moment SortableJS recognises the drag.
                    // evt.originalEvent is the underlying mouse/touch/pointer event.
                    const startX = evt.originalEvent ? readClientX(evt.originalEvent) : null;
                    this.dragStartMouseX = startX !== null ? startX : this.lastMouseX;
                    this.dragStartDepth = parseInt(this.draggedRow.dataset.depth || '0', 10);

                    // Identify descendants (rows immediately following with depth > base)
                    // and remember their depth relative to the dragged parent. They stay
                    // in DOM and visible — we'll move them as a block whenever the parent
                    // moves or its depth changes, so the user always sees the entire
                    // subtree travelling together with the correct hierarchy preserved.
                    const allRows = this.getAllRows();
                    const startIdx = allRows.indexOf(this.draggedRow);
                    this.descendantRows = [];
                    if (startIdx !== -1) {
                        const baseDepth = this.dragStartDepth;
                        for (let i = startIdx + 1; i < allRows.length; i++) {
                            const d = parseInt(allRows[i].dataset.depth || '0', 10);
                            if (d > baseDepth) {
                                this.descendantRows.push({
                                    row: allRows[i],
                                    relDepth: d - baseDepth,
                                });
                            } else {
                                break;
                            }
                        }
                    }

                    // Mark descendants so SortableJS won't try to drop other items
                    // between them while we drag the parent.
                    this.descendantRows.forEach(({row}) => {
                        row.classList.add('menu-row--descendant-of-drag');
                    });

                    document.body.classList.add('dragging-menu-item');
                },

                onDragEnd() {
                    if (!this.draggedRow) {
                        document.body.classList.remove('dragging-menu-item');
                        return;
                    }

                    this.enforceDepthBounds();
                    // Final snap — make sure descendants sit immediately after parent
                    // with correct depths after SortableJS has settled the parent's
                    // final DOM position.
                    this.snapDescendantsToParent();

                    this.descendantRows.forEach(({row}) => {
                        row.classList.remove('menu-row--descendant-of-drag');
                    });
                    this.descendantRows = [];
                    this.draggedRow = null;
                    document.body.classList.remove('dragging-menu-item');

                    this.updateStructure();
                },

                /**
                 * Move descendants to sit immediately after the dragged parent in DOM
                 * order, mirroring what the user expects to see when dragging a subtree.
                 * Also re-applies depths so the visible hierarchy stays correct.
                 */
                snapDescendantsToParent() {
                    if (!this.draggedRow) return;
                    if (!this.descendantRows.length) return;

                    const parentDepth = parseInt(this.draggedRow.dataset.depth || '0', 10);
                    let prev = this.draggedRow;

                    this.descendantRows.forEach(({row, relDepth}) => {
                        if (!row.isConnected) return;
                        const newDepth = Math.min(MENU_MAX_DEPTH, parentDepth + relDepth);
                        // Apply depth without recursing through this method
                        row.dataset.depth = String(newDepth);
                        row.style.paddingInlineStart = (newDepth * MENU_INDENT_PX) + 'px';
                        row.classList.toggle('menu-row--nested', newDepth > 0);

                        // Place row immediately after `prev` if it isn't already there.
                        if (prev.nextSibling !== row) {
                            prev.parentElement.insertBefore(row, prev.nextSibling);
                        }
                        prev = row;
                    });
                },

                /**
                 * Compute the [min, max] depth band the dragged row may legally occupy
                 * given its sibling neighbours in the flat DOM.
                 *
                 * The dragged row's own descendants are skipped — they travel with the
                 * parent and must not constrain the parent's depth (otherwise dragging
                 * a parent that has children would always be locked to its current
                 * depth because the next row would be a child at depth+1, forcing
                 * min > max).
                 *
                 * Rules:
                 *   - `max = (prev non-descendant sibling's depth) + 1`, capped at
                 *     MENU_MAX_DEPTH. With no prev row, max = 0.
                 *   - `min = 0`. Outdenting any row to root is always permitted.
                 *
                 * Note on `min`:
                 *   We deliberately do NOT clamp `min` to the next row's depth, even
                 *   though that would keep the next row's parent chain intact. Clamping
                 *   would prevent outdenting any non-last child (the next sibling at
                 *   the same depth would force min = currentDepth, locking the row in
                 *   place). That contradicts every modern menu builder UX.
                 *
                 *   When a middle child is outdented to a shallower depth, subsequent
                 *   same-depth siblings naturally re-parent under the outdented row in
                 *   `buildTreeFromFlatDOM` (depth-based stack walking). Users who want
                 *   the middle child gone *without* the cascade should drag it past
                 *   its remaining siblings before outdenting, or use the Out button.
                 */
                computeDepthBounds(row) {
                    const rows = this.getAllRows();
                    const idx = rows.indexOf(row);
                    if (idx === -1) return { min: 0, max: 0 };

                    // Find prev row that isn't part of the dragged subtree
                    let prev = null;
                    for (let i = idx - 1; i >= 0; i--) {
                        if (!rows[i].classList.contains('menu-row--descendant-of-drag')) {
                            prev = rows[i];
                            break;
                        }
                    }

                    const prevDepth = prev ? parseInt(prev.dataset.depth || '0', 10) : -1;

                    const min = 0;
                    const max = Math.min(MENU_MAX_DEPTH, Math.max(0, prevDepth + 1));

                    return { min, max };
                },

                updateDepthFromMouse() {
                    if (!this.draggedRow) return;
                    const deltaX = (this.lastMouseX - this.dragStartMouseX)
                        * (this.isRtl ? -1 : 1);

                    const current = parseInt(this.draggedRow.dataset.depth || '0', 10);

                    // Hysteresis: nominal step is 1 depth per MENU_INDENT_PX, but we
                    // only commit a step once the cursor has moved far enough past
                    // the prior step's mid-point to avoid jitter.
                    const rawSteps = deltaX / MENU_INDENT_PX;
                    const fromStart = Math.round(rawSteps);
                    const overshoot = (deltaX - fromStart * MENU_INDENT_PX);
                    let requested = this.dragStartDepth + fromStart;
                    if (Math.abs(overshoot) > MENU_DEPTH_HYSTERESIS_PX) {
                        requested += overshoot > 0 ? 1 : -1;
                    }

                    const bounds = this.computeDepthBounds(this.draggedRow);
                    const clamped = Math.max(bounds.min, Math.min(bounds.max, requested));
                    if (clamped !== current) {
                        this.setRowDepth(this.draggedRow, clamped);
                    }
                },

                /**
                 * Final-pass depth clamp after SortableJS reorders the row. The dragged row
                 * may have been at a depth that's now invalid (e.g. trying to be a child
                 * of a row that no longer precedes it). Clamp to the new bounds.
                 */
                enforceDepthBounds() {
                    if (!this.draggedRow) return;
                    const bounds = this.computeDepthBounds(this.draggedRow);
                    let depth = parseInt(this.draggedRow.dataset.depth || '0', 10);
                    if (depth < bounds.min) depth = bounds.min;
                    if (depth > bounds.max) depth = bounds.max;
                    this.setRowDepth(this.draggedRow, depth);
                },

                setRowDepth(row, depth) {
                    const safe = Math.max(0, Math.min(MENU_MAX_DEPTH, depth));
                    row.dataset.depth = String(safe);
                    row.style.paddingInlineStart = (safe * MENU_INDENT_PX) + 'px';
                    row.classList.toggle('menu-row--nested', safe > 0);

                    if (row === this.draggedRow) {
                        // Mirror the indent change onto the visible follower clone
                        // (forceFallback: true clones the source on drag start).
                        const clone = document.querySelector('.menu-row-fallback');
                        if (clone) {
                            clone.style.paddingInlineStart = (safe * MENU_INDENT_PX) + 'px';
                            clone.dataset.depth = String(safe);
                        }

                        // Keep the dragged subtree visually consistent: every descendant
                        // shifts by the same delta so the hierarchy is preserved
                        // throughout the drag, never collapsed onto the parent's depth.
                        this.descendantRows.forEach(({row: descRow, relDepth}) => {
                            const descDepth = Math.min(MENU_MAX_DEPTH, safe + relDepth);
                            descRow.dataset.depth = String(descDepth);
                            descRow.style.paddingInlineStart = (descDepth * MENU_INDENT_PX) + 'px';
                            descRow.classList.toggle('menu-row--nested', descDepth > 0);
                        });
                    }
                },

                updateStructure() {
                    this.$wire.updateMenuStructure(this.buildTreeFromFlatDOM());
                },

                /**
                 * Walk the flat DOM and rebuild a nested {id, children} tree using
                 * each row's data-depth + sibling order. Uses a stack of "current path"
                 * frames; whenever we encounter a row at depth ≤ stack-top depth we pop
                 * frames until the new row would be a child of the stack-top.
                 */
                buildTreeFromFlatDOM() {
                    const rows = this.getAllRows();
                    const tree = [];
                    // Sentinel frame at depth -1 holding the root array
                    const stack = [{ depth: -1, children: tree }];

                    rows.forEach((row) => {
                        const id = parseInt(row.dataset.id, 10);
                        if (Number.isNaN(id)) return;
                        let depth = parseInt(row.dataset.depth || '0', 10);

                        // Pop until top is a valid parent (its depth < this depth)
                        while (stack.length > 1 && stack[stack.length - 1].depth >= depth) {
                            stack.pop();
                        }
                        // If the requested depth jumped (e.g. depth 3 with parent at 0),
                        // clamp to top.depth + 1 to keep the tree well-formed.
                        const parent = stack[stack.length - 1];
                        if (depth > parent.depth + 1) {
                            depth = parent.depth + 1;
                        }

                        const node = { id, children: [] };
                        parent.children.push(node);
                        stack.push({ depth, children: node.children });
                    });

                    return tree;
                },
            }));
        });
    </script>
    @endpush

    <style>
        /* ─── Sortable drag visuals ─────────────────────────────────────── */
        .sortable-ghost {
            opacity: 0.4;
            background: rgb(245 243 255);
            border-radius: 0.75rem;
            outline: 1.5px dashed rgb(139 92 246 / 0.45);
            outline-offset: -1.5px;
            box-shadow: none;
        }
        .sortable-ghost .menu-row-content {
            border-color: transparent !important;
            background: transparent;
            box-shadow: none !important;
        }
        .dark .sortable-ghost {
            background: rgb(109 40 217 / 0.1);
            outline-color: rgb(139 92 246 / 0.35);
        }
        .sortable-chosen {
            box-shadow: 0 6px 20px -6px rgb(0 0 0 / 0.1), 0 0 0 2px rgb(124 58 237 / 0.2);
            border-radius: 0.75rem;
        }
        .dark .sortable-chosen {
            box-shadow: 0 10px 28px -8px rgb(0 0 0 / 0.55), 0 0 0 1.5px rgb(139 92 246 / 0.3);
        }

        /* The clone that follows the cursor when forceFallback: true */
        .menu-row-fallback {
            opacity: 0.92;
            box-shadow: 0 14px 36px -8px rgb(0 0 0 / 0.18), 0 0 0 1px rgb(124 58 237 / 0.25);
            cursor: grabbing !important;
            pointer-events: none;
            transition: padding-inline-start 0.12s cubic-bezier(0.2, 0.8, 0.2, 1);
        }
        .dark .menu-row-fallback {
            box-shadow: 0 18px 40px -10px rgb(0 0 0 / 0.6), 0 0 0 1px rgb(139 92 246 / 0.45);
        }
        .menu-row-fallback .menu-row-guide { display: none; }

        /* Visual cue for descendants of the row currently being dragged: subtly
           tinted so the user perceives the dragged subtree as a single block. */
        .menu-row--descendant-of-drag {
            opacity: 0.78;
        }
        .menu-row--descendant-of-drag > .menu-row-content {
            background: rgb(245 243 255 / 0.55);
            border-color: rgb(139 92 246 / 0.18) !important;
        }
        .dark .menu-row--descendant-of-drag > .menu-row-content {
            background: rgb(109 40 217 / 0.07);
            border-color: rgb(139 92 246 / 0.18) !important;
        }

        /* ─── Flat-list rows ────────────────────────────────────────────── */
        .menu-row {
            position: relative;
            transition: padding-inline-start 0.16s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        /* Vertical depth guides (dotted lines at each indent step) */
        .menu-row-guide {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 1px;
            border-inline-start: 1.5px dotted rgb(228 228 231);
            pointer-events: none;
        }
        .dark .menu-row-guide {
            border-inline-start-color: rgb(255 255 255 / 0.08);
        }
        body.dragging-menu-item .menu-row-guide {
            border-inline-start-color: rgb(139 92 246 / 0.35);
        }
        .dark body.dragging-menu-item .menu-row-guide {
            border-inline-start-color: rgb(139 92 246 / 0.3);
        }

        /* Subtle depth tint on the row content for clarity */
        .menu-row--nested > .menu-row-content {
            border-color: rgb(228 228 231 / 0.7);
        }
        .dark .menu-row--nested > .menu-row-content {
            border-color: rgb(255 255 255 / 0.05);
        }

        /* While dragging, keep the dragged ghost above guides cleanly */
        body.dragging-menu-item .menu-row.sortable-ghost .menu-row-guide {
            display: none;
        }
    </style>
</x-filament-panels::page>
