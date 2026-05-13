<div
    id="mksine-page-builder-root"
    class="flex w-full flex-col"
    :class="fullScreen ? 'fixed inset-0 z-50 overflow-y-auto overscroll-contain bg-zinc-50 dark:bg-zinc-950' : ''"
    x-data="mksinePageBuilder()"
    @keydown.escape.window="$wire.closePasteModal(); fullScreen = false"
    @modal-closed.window="if ($event.detail?.id === 'template-picker-modal') $wire.closeTemplatePanel(); if ($event.detail?.id === 'block-editor-modal') $wire.closeEditor(); if ($event.detail?.id === 'component-picker-modal') $wire.closeComponentPanel()"
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
            @media (prefers-reduced-motion: reduce) {
                .mksine-component-picker-panel { animation: none; }
            }

            /* Filament modal overrides — block editor, template picker, component picker */
            #block-editor-modal .fi-modal-window,
            #template-picker-modal .fi-modal-window,
            #component-picker-modal .fi-modal-window {
                border-radius: 16px;
                border: 1px solid rgb(228 228 231);
                background: #fff;
                box-shadow: 0 20px 60px -12px rgb(0 0 0 / 0.15), 0 0 0 1px rgb(0 0 0 / 0.03);
                overflow: clip; /* clip visually without blocking scroll */
            }
            .dark #block-editor-modal .fi-modal-window,
            .dark #template-picker-modal .fi-modal-window,
            .dark #component-picker-modal .fi-modal-window {
                border-color: rgb(255 255 255 / 0.07);
                background: rgb(24 24 27);
                box-shadow: 0 24px 64px -12px rgb(0 0 0 / 0.6), 0 0 0 1px rgb(255 255 255 / 0.04);
            }

            #block-editor-modal .fi-modal-close-overlay,
            #template-picker-modal .fi-modal-close-overlay,
            #component-picker-modal .fi-modal-close-overlay {
                background: rgb(9 9 11 / 0.55);
                backdrop-filter: blur(4px);
            }
        </style>

        <div class="flex w-full flex-col">
            @include('mksine::page-builder.components.toolbar')

            {{-- Canvas --}}
            <div class="relative z-0 w-full bg-zinc-50 px-4 py-5 dark:bg-zinc-950">
                @include('mksine::page-builder.components.block-list')
            </div>
        </div>
    </div>

    @include('mksine::page-builder.components.modals', [
        'editorHeading' => $this->editorHeading,
        'editingBlockId' => $editingBlockId,
        'editingBlockData' => $editingBlockData ?? [],
        'showTemplatePanel' => $showTemplatePanel,
        'templatesByCategory' => $this->templatesByCategory,
    ])
    @include('mksine::page-builder.components.paste-overlay')
</div>
