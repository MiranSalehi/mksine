@if($editingBlockId)
    <x-filament::modal id="block-editor-modal" :heading="$editorHeading" width="2xl" role="dialog" aria-modal="true" :aria-labelledby="'block-editor-modal-title'">
        <div wire:key="block-editor-wrap-{{ $editingBlockId }}">
            @livewire('mksine::component-editor', [
                'blockId' => $editingBlockId,
                'blockType' => $editingBlockData['block']['type'] ?? '',
                'blockData' => $editingBlockData['block']['data'] ?? [],
                'parentId' => $editingBlockData['parentId'] ?? null,
                'columnIndex' => $editingBlockData['columnIndex'] ?? null,
            ], 'block-editor-'.$editingBlockId)
        </div>
    </x-filament::modal>
@endif

@if($showTemplatePanel)
    <x-filament::modal id="template-picker-modal" :heading="__('mksine::page_builder.choose_template')" width="4xl" role="dialog" aria-modal="true">
        @include('mksine::page-builder.partials.template-picker-content', ['templatesByCategory' => $templatesByCategory])
        <x-slot:footer>
            <x-filament::button color="gray" wire:click="closeTemplatePanel">
                {{ __('mksine::page_builder.cancel') }}
            </x-filament::button>
        </x-slot:footer>
    </x-filament::modal>
@endif
