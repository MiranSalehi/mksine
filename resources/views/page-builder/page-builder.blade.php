<div
    id="mksine-page-builder-root"
    class="flex w-full flex-col"
    :class="fullScreen ? 'is-workspace' : ''"
    :style="fullScreen ? 'position:fixed;inset:0;z-index:30;display:flex;flex-direction:column;overflow:hidden' : ''"
    x-data='Object.assign(mksinePageBuilder(), { viewport: @json($previewViewport) })'
    x-effect="document.body.classList.toggle('overflow-hidden', fullScreen)"
    @keydown.escape.window="if ($wire.showPasteModal) { $wire.closePasteModal(); return } if ($wire.editingBlockId) { $wire.closeEditor(); return } if ($wire.showComponentPanel || $wire.showTemplatePanel) return; fullScreen = false"
    @builder-editor-closed.window="document.querySelector('#mksine-page-builder-root iframe')?.contentDocument?.querySelectorAll('.is-selected').forEach((el) => el.classList.remove('is-selected'))"
    @message.window="
        const data = $event.data;
        if (!data || data.source !== 'mksine-page-builder' || $event.origin !== window.location.origin) return;
        if (data.type === 'move') return;
        if (window.__mksineBuilderPreviewBound) return;
        if (data.type === 'insert') { $wire.addBlockAtPosition(data.position === 0 || data.position ? data.position : 0, data.parentId || null, data.columnIndex === 0 || data.columnIndex ? data.columnIndex : null); return }
        if (!data.blockId) return;
        if (data.type === 'remove') { $wire.removeBlock(data.blockId, data.parentId || null, data.columnIndex === 0 || data.columnIndex ? data.columnIndex : null); return }
        if (data.type === 'move-up') { $wire.moveBlockUp(data.blockId, data.parentId || null, data.columnIndex === 0 || data.columnIndex ? data.columnIndex : null); return }
        if (data.type === 'move-down') { $wire.moveBlockDown(data.blockId, data.parentId || null, data.columnIndex === 0 || data.columnIndex ? data.columnIndex : null); return }
        if (data.type === 'duplicate') { $wire.duplicateBlock(data.blockId, data.parentId || null, data.columnIndex === 0 || data.columnIndex ? data.columnIndex : null); return }
        if (data.type === 'inline-heading') { $wire.updateHeadingText(data.blockId, data.text || ''); return }
        if (data.type === 'select') return;
        $wire.editBlock(data.blockId, data.parentId || null, data.columnIndex === 0 || data.columnIndex ? data.columnIndex : null, data.region || null, data.index === 0 || data.index ? data.index : null)
    "
    @modal-closed.window="if ($event.detail?.id === 'template-picker-modal') $wire.closeTemplatePanel(); if ($event.detail?.id === 'component-picker-modal') $wire.closeComponentPanel(); if ($event.detail?.id === 'block-editor-slideover' && $wire.editingBlockId) $wire.closeEditor()"
    @pagebuilder:show-paste-box.window="openPasteBox($event.detail?.position)"
>
    <div class="mksine-page-builder flex w-full flex-col" wire:ignore.self>
        <style>
            [x-cloak] { display: none !important; }

            /* Drag states */
            .sortable-ghost {
                opacity: 0.45;
                background: rgb(245 243 255);
                border-radius: 0.75rem;
                border: 1.5px dashed rgb(139 92 246 / 0.5);
                box-shadow: none;
            }
            .dark .sortable-ghost {
                background: rgb(109 40 217 / 0.1);
                border-color: rgb(139 92 246 / 0.4);
            }
            .sortable-chosen {
                box-shadow: 0 8px 24px -6px rgb(0 0 0 / 0.12), 0 0 0 2px rgb(124 58 237 / 0.2);
                transform: scale(1.01);
            }
            .dark .sortable-chosen {
                box-shadow: 0 12px 32px -8px rgb(0 0 0 / 0.6), 0 0 0 1.5px rgb(139 92 246 / 0.35);
            }
            .sortable-drag { opacity: 1; }

            /* Component picker panel enter animation */
            @keyframes mksine-component-picker-panel-in {
                from { opacity: 0; transform: translateY(4px); }
                to   { opacity: 1; transform: translateY(0);   }
            }
            .mksine-component-picker-panel {
                animation: mksine-component-picker-panel-in 0.22s cubic-bezier(0.22, 1, 0.36, 1) both;
            }
            .mksine-picker-miss { display: none; }
            .mksine-component-picker-panel.is-searching:not(:has([data-mksine-picker-card]:not([hidden]))) .mksine-picker-miss {
                display: block;
            }
            @media (prefers-reduced-motion: reduce) {
                .mksine-component-picker-panel { animation: none; }
                .mksine-pb-frame { transition: none; }
            }

            /* Filament modal overrides — template picker, component picker */
            #template-picker-modal .fi-modal-window,
            #component-picker-modal .fi-modal-window {
                border-radius: 16px;
                border: 1px solid rgb(228 228 231);
                background: #fff;
                box-shadow: 0 20px 60px -12px rgb(0 0 0 / 0.15), 0 0 0 1px rgb(0 0 0 / 0.03);
                overflow: clip;
            }
            .mksine-pb-properties .fi-dropdown-panel {
                z-index: 60;
            }
            .dark #template-picker-modal .fi-modal-window,
            .dark #component-picker-modal .fi-modal-window,
            .dark .mksine-pb-properties {
                border-color: rgb(255 255 255 / 0.07);
                background: rgb(24 24 27);
                box-shadow: 0 24px 64px -12px rgb(0 0 0 / 0.6), 0 0 0 1px rgb(255 255 255 / 0.04);
            }

            #template-picker-modal .fi-modal-close-overlay,
            #component-picker-modal .fi-modal-close-overlay {
                background: rgb(9 9 11 / 0.55);
                backdrop-filter: blur(4px);
            }

            .mksine-pb-stage {
                display: block;
            }
            .mksine-pb-workspace {
                display: flex;
                align-items: stretch;
                gap: 12px;
                width: 100%;
                min-width: 0;
            }
            .mksine-pb-canvas {
                flex: 1 1 auto;
                min-width: 0;
            }
            #block-editor-slideover.fi-modal-slide-over > .fi-modal-window-ctn > .fi-modal-window {
                overflow: visible;
            }
            #block-editor-slideover .fi-modal-content {
                overflow: auto;
                max-height: calc(100dvh - 4.5rem);
            }
            .mksine-pb-properties {
                overflow: visible;
            }
            .mksine-pb-frame {
                height: min(860px, calc(100vh - 180px));
                min-height: 640px;
                width: 100%;
                overflow: hidden;
                transition: width 150ms ease;
                border: 1px solid rgb(228 228 231);
                border-radius: 12px;
                background: #fff;
            }
            .mksine-pb-frame.is-empty {
                height: auto;
                min-height: 0;
                border-color: rgb(221 214 254);
                background: linear-gradient(90deg, rgb(245 243 255) 0%, rgb(255 255 255) 52%);
            }
            .dark .mksine-pb-frame.is-empty {
                border-color: rgb(139 92 246 / 0.35);
                background: linear-gradient(90deg, rgb(46 16 101 / 0.5) 0%, rgb(9 9 11) 58%);
            }
            .mksine-pb-empty:hover .mksine-pb-empty-cta {
                background: rgb(109 40 217);
            }
            .mksine-pb-frame.is-tablet { width: 768px; max-width: 100%; margin-inline: auto; }
            .mksine-pb-frame.is-mobile { width: 390px; max-width: 100%; margin-inline: auto; }
            .mksine-pb-frame iframe,
            .mksine-pb-frame > div:first-child {
                display: block;
                width: 100%;
                height: 100%;
                border: 0;
                background: #fff;
            }
            .dark .mksine-pb-frame {
                border-color: rgb(255 255 255 / 0.08);
            }
            #mksine-page-builder-root.is-workspace .mksine-page-builder,
            #mksine-page-builder-root.is-workspace .mksine-page-builder > .flex {
                display: flex;
                min-height: 0;
                flex: 1;
                flex-direction: column;
            }
            #mksine-page-builder-root.is-workspace .mksine-pb-stage {
                display: flex;
                min-height: 0;
                flex: 1;
                flex-direction: column;
                background: rgb(244 244 245);
                padding: 16px;
            }
            #mksine-page-builder-root.is-workspace {
                background: rgb(244 244 245);
            }
            .dark #mksine-page-builder-root.is-workspace,
            .dark #mksine-page-builder-root.is-workspace .mksine-pb-stage {
                background: rgb(9 9 11);
            }
            #mksine-page-builder-root.is-workspace .mksine-pb-workspace {
                min-height: 0;
                flex: 1;
            }
            #mksine-page-builder-root.is-workspace .mksine-pb-canvas {
                display: flex;
                min-height: 0;
                flex: 1;
                flex-direction: column;
            }
            #mksine-page-builder-root.is-workspace .mksine-pb-frame {
                height: auto;
                min-height: 0;
                flex: 1;
                box-shadow: 0 12px 40px rgb(0 0 0 / 0.08);
            }
        </style>

        <div class="flex w-full flex-col">
            @include('mksine::page-builder.components.toolbar')

            <div class="mksine-pb-stage bg-zinc-50 px-4 py-5 dark:bg-zinc-950">
                <div class="mksine-pb-workspace">
                <div
                    class="mksine-pb-canvas relative z-0 min-w-0"
                    wire:loading.attr="aria-busy"
                    wire:target="addBlock,loadTemplate,undo,redo,duplicateBlock,removeBlock,saveBlock,reorderBlocks,reorderColumnBlocks,reorderNavigator,moveNavigatorBlock,moveBlockAfterDrag,editBlock,openComponentPanel,toggleTemplatePanel,updateHeadingText,unlinkSection,insertSection"
                >
                    <div class="mb-3 flex items-center gap-2" x-show="!fullScreen" x-cloak>
                        @foreach (['desktop' => __('mksine::page_builder.viewport_desktop'), 'tablet' => __('mksine::page_builder.viewport_tablet'), 'mobile' => __('mksine::page_builder.viewport_mobile')] as $viewport => $label)
                            <button
                                type="button"
                                @click="viewport = '{{ $viewport }}'"
                                :aria-pressed="viewport === '{{ $viewport }}'"
                                :class="viewport === '{{ $viewport }}' ? 'bg-violet-600 text-white' : 'bg-white text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-white/10'"
                                class="rounded-md px-2.5 py-1 text-xs font-medium"
                            >{{ $label }}</button>
                        @endforeach
                    </div>
                    <div
                        @class([
                            'mksine-pb-frame relative',
                            'is-empty' => $this->blocks === [],
                        ])
                        :class="{ 'is-tablet': viewport === 'tablet', 'is-mobile': viewport === 'mobile' }"
                    >
                        @if ($this->blocks === [])
                            <button
                                type="button"
                                wire:click="addBlockAtPosition(0)"
                                wire:loading.attr="disabled"
                                wire:target="addBlockAtPosition"
                                class="mksine-pb-empty flex w-full items-center gap-3 bg-transparent px-3.5 py-3 text-start"
                            >
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-violet-600 shadow-sm ring-1 ring-violet-100 dark:bg-zinc-900 dark:text-violet-300 dark:ring-white/10">
                                    <x-heroicon-o-squares-2x2 class="h-5 w-5" aria-hidden="true" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">{{ __('mksine::page_builder.your_page_is_empty') }}</span>
                                    <span class="mt-0.5 block truncate text-xs text-zinc-500 dark:text-zinc-400">{{ __('mksine::page_builder.start_building_by_adding') }}</span>
                                </span>
                                <span class="mksine-pb-empty-cta inline-flex h-8 shrink-0 items-center justify-center gap-1.5 whitespace-nowrap rounded-lg bg-violet-600 px-3 text-xs font-semibold text-white shadow-[0_2px_8px_0_rgb(124_58_237/0.3)]">
                                    <span class="relative inline-flex h-3.5 w-3.5 shrink-0 items-center justify-center">
                                        <span wire:loading.class="opacity-0" wire:target="addBlockAtPosition">
                                            <x-heroicon-o-plus class="h-3.5 w-3.5" />
                                        </span>
                                        <span
                                            class="pointer-events-none absolute inset-0 hidden items-center justify-center"
                                            wire:loading.flex
                                            wire:target="addBlockAtPosition"
                                        >
                                            <x-filament::loading-indicator class="h-3.5 w-3.5 shrink-0" />
                                        </span>
                                    </span>
                                    {{ __('mksine::page_builder.add_component') }}
                                </span>
                            </button>
                        @else
                        <div
                            class="h-full w-full"
                            x-data="{
                                src: @entangle('previewUrl'),
                                shown: '',
                                timer: null,
                                init() {
                                    this.shown = this.src || '';
                                    this.$watch('src', (value) => {
                                        window.__mksineCapturePreviewScroll?.();
                                        clearTimeout(this.timer);
                                        this.timer = setTimeout(() => { this.shown = value || ''; }, 300);
                                    });
                                },
                            }"
                        >
                            <iframe
                                x-ref="previewFrame"
                                x-show="shown"
                                :src="shown"
                                title="{{ __('mksine::page_builder.canvas_preview') }}"
                                class="h-full w-full bg-white"
                                @load="window.__mksineRestorePreviewScroll?.($event.target)"
                            ></iframe>
                        </div>
                        @endif
                        <div
                            class="pointer-events-auto absolute inset-0 z-10 hidden items-center justify-center bg-white/55 dark:bg-zinc-950/55"
                            wire:loading.flex
                            wire:target="addBlock,loadTemplate,undo,redo,duplicateBlock,removeBlock,saveBlock,reorderBlocks,reorderColumnBlocks,reorderNavigator,moveNavigatorBlock,moveBlockAfterDrag"
                            aria-hidden="true"
                        >
                            <div class="pointer-events-none flex h-10 w-10 items-center justify-center rounded-full bg-white/90 shadow-sm dark:bg-zinc-900/90">
                                <x-filament::loading-indicator class="h-5 w-5 text-violet-600 dark:text-violet-400" />
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>

    <div
        x-data
        x-effect="const open = !!$wire.editingBlockId; $nextTick(() => { if (open) { $dispatch('open-modal', { id: 'block-editor-slideover' }) } else { $dispatch('close-modal', { id: 'block-editor-slideover' }) } })"
    >
        <x-filament::modal
            id="block-editor-slideover"
            slide-over
            width="lg"
            teleport="body"
            :heading="$editingBlockId ? $this->editorHeading : __('mksine::page_builder.edit')"
            :close-by-clicking-away="true"
            :close-by-escaping="true"
        >
            @if ($editingBlockId)
                <div class="mksine-pb-properties" wire:key="block-editor-wrap-{{ $editingBlockId }}-{{ $editingBlockData['region'] ?? 'all' }}-{{ $editingBlockData['regionIndex'] ?? 'x' }}">
                    @livewire('mksine::component-editor', [
                        'blockId' => $editingBlockId,
                        'blockType' => $editingBlockData['block']['type'] ?? '',
                        'blockData' => $editingBlockData['block']['data'] ?? [],
                        'parentId' => $editingBlockData['parentId'] ?? null,
                        'columnIndex' => $editingBlockData['columnIndex'] ?? null,
                        'presentation' => $editingBlockData['block']['presentation'] ?? [],
                        'region' => $editingBlockData['region'] ?? null,
                        'regionIndex' => $editingBlockData['regionIndex'] ?? null,
                    ], 'block-editor-'.$editingBlockId.'-'.($editingBlockData['region'] ?? 'all').'-'.($editingBlockData['regionIndex'] ?? 'x'))
                </div>
            @endif
        </x-filament::modal>
    </div>

    @include('mksine::page-builder.components.modals', [
        'editorHeading' => $this->editorHeading,
        'editingBlockId' => $editingBlockId,
        'editingBlockData' => $editingBlockData ?? [],
        'showTemplatePanel' => $showTemplatePanel,
        'templatesByCategory' => $this->templatesByCategory,
    ])
    @include('mksine::page-builder.components.paste-overlay')
    <script>
        window.__mksinePreviewScroll = window.__mksinePreviewScroll || 0;
        window.__mksineBuilderPageName = function (root) {
            const form = root && root.closest ? root.closest('form') : null;
            const input = form ? form.querySelector('[id="form.page_information.title"]') : null;
            return input && input.value ? input.value.trim() : '';
        };
        window.__mksineCapturePreviewScroll = function () {
            const frame = document.querySelector('#mksine-page-builder-root iframe');
            window.__mksinePageScroll = window.scrollY || 0;
            if (!frame || !frame.contentWindow) {
                return;
            }
            try {
                const doc = frame.contentDocument;
                const y = frame.contentWindow.scrollY || 0;
                const scrollable = !!doc && (doc.documentElement.scrollHeight - doc.documentElement.clientHeight) > 80;
                if (y > 0 || scrollable) {
                    window.__mksinePreviewScroll = y;
                }
            } catch (e) {}
        };
        window.__mksineRestorePreviewScroll = function (frame) {
            const y = window.__mksinePreviewScroll || 0;
            if (!frame || !y) {
                return;
            }
            let tries = 0;
            const apply = function () {
                tries += 1;
                try {
                    frame.contentWindow.scrollTo(0, y);
                    if (tries < 12 && Math.abs((frame.contentWindow.scrollY || 0) - y) > 40) {
                        setTimeout(apply, 120);
                    }
                } catch (e) {}
            };
            apply();
        };
        if (!window.__mksinePreviewScrollBound) {
            window.__mksinePreviewScrollBound = true;
            const bindPreviewScroll = function () {
                window.Livewire.hook('commit', function ({ component, succeed }) {
                    const root = document.getElementById('mksine-page-builder-root');
                    if (!root || !component.el || (component.el !== root && !component.el.contains(root))) {
                        return;
                    }
                    window.__mksineCapturePreviewScroll();
                    const pageY = window.__mksinePageScroll || 0;
                    succeed(function () {
                        window.scrollTo(0, pageY);
                    });
                });
            };
            try {
                if (window.Livewire) {
                    bindPreviewScroll();
                } else {
                    document.addEventListener('livewire:init', bindPreviewScroll);
                }
            } catch (e) {}
        }
        if (!window.__mksineBuilderPreviewBound) {
            window.__mksineBuilderPreviewBound = true;
            window.addEventListener('message', function (event) {
                if (event.origin !== window.location.origin) {
                    return;
                }
                const data = event.data;
                if (!data || data.source !== 'mksine-page-builder') {
                    return;
                }
                const root = document.getElementById('mksine-page-builder-root');
                const wireId = root && root.getAttribute('wire:id');
                if (!wireId || !window.Livewire) {
                    return;
                }
                const component = window.Livewire.find(wireId);
                if (!component) {
                    return;
                }
                if (data.type === 'move' && (data.movedId || data.blockId)) {
                    component.call('moveBlockAfterDrag', data.movedId || data.blockId, data.fromParentId || null, data.fromColumnIndex === 0 || data.fromColumnIndex ? data.fromColumnIndex : null, data.toParentId || null, data.toColumnIndex === 0 || data.toColumnIndex ? data.toColumnIndex : null, data.index === 0 || data.index ? data.index : 0);
                    return;
                }
                if (data.type === 'insert') {
                    component.call('addBlockAtPosition', data.position === 0 || data.position ? data.position : 0, data.parentId || null, data.columnIndex === 0 || data.columnIndex ? data.columnIndex : null);
                    return;
                }
                if (!data.blockId) {
                    return;
                }
                if (data.type === 'remove') {
                    component.call('removeBlock', data.blockId, data.parentId || null, data.columnIndex === 0 || data.columnIndex ? data.columnIndex : null);
                    return;
                }
                if (data.type === 'move-up') {
                    component.call('moveBlockUp', data.blockId, data.parentId || null, data.columnIndex === 0 || data.columnIndex ? data.columnIndex : null);
                    return;
                }
                if (data.type === 'move-down') {
                    component.call('moveBlockDown', data.blockId, data.parentId || null, data.columnIndex === 0 || data.columnIndex ? data.columnIndex : null);
                    return;
                }
                if (data.type === 'duplicate') {
                    component.call('duplicateBlock', data.blockId, data.parentId || null, data.columnIndex === 0 || data.columnIndex ? data.columnIndex : null);
                    return;
                }
                if (data.type === 'inline-heading') {
                    component.call('updateHeadingText', data.blockId, data.text || '');
                    return;
                }
                if (data.type === 'select') {
                    return;
                }
                component.call('editBlock', data.blockId, data.parentId || null, data.columnIndex === 0 || data.columnIndex ? data.columnIndex : null, data.region || null, data.index === 0 || data.index ? data.index : null);
            });
        }
    </script>
</div>
