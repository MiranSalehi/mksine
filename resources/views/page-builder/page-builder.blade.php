<div
    class="flex h-full min-h-[calc(100vh-10rem)] flex-col"
    id="mksine-page-builder-root"
    x-data="mksinePageBuilder()"
    x-bind:class="fullScreen ? 'fixed inset-0 z-50 bg-white dark:bg-gray-900' : ''"
    @keydown.escape.window="$wire.closePasteModal(); fullScreen = false"
    @modal-closed.window="if ($event.detail?.id === 'template-picker-modal') $wire.closeTemplatePanel(); if ($event.detail?.id === 'block-editor-modal') $wire.closeEditor(); if ($event.detail?.id === 'component-picker-modal') $wire.closeComponentPanel()"
    @pagebuilder:show-paste-box.window="openPasteBox($event.detail?.position)"
>
    <div class="mksine-page-builder flex h-full min-h-0 flex-col" wire:ignore.self>
        <style>
            [x-cloak] { display: none !important; }
            .sortable-ghost {
                opacity: 0.35;
                background: linear-gradient(135deg, rgb(236 72 153 / 0.08) 0%, rgb(147 51 234 / 0.08) 100%);
                border-radius: 1rem;
                border: 2px dashed rgb(147 51 234 / 0.5);
            }
            .sortable-chosen {
                box-shadow: 0 20px 50px -12px rgb(0 0 0 / 0.25);
                border-color: rgb(147 51 234 / 0.4);
                transform: scale(1.01);
            }
            .sortable-drag { opacity: 1; }

            @keyframes mksine-component-picker-panel-in {
                from {
                    opacity: 0;
                    transform: translateY(0.375rem);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            .mksine-component-picker-panel {
                animation: mksine-component-picker-panel-in 0.28s cubic-bezier(0.22, 1, 0.36, 1) both;
            }
            @media (prefers-reduced-motion: reduce) {
                .mksine-component-picker-panel {
                    animation: none;
                }
            }
        </style>

        <div class="flex min-h-0 flex-1 flex-col">
            @include('mksine::page-builder.components.toolbar')

            <div class="relative z-0 min-h-0 flex-1 overflow-x-hidden overflow-y-auto px-3 py-4">
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
