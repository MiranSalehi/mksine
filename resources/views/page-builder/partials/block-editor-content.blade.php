<div wire:key="block-editor-wrap-{{ $blockId }}">
    @livewire('mksine::component-editor', [
        'blockId' => $blockId,
        'blockType' => $blockType,
        'blockData' => $blockData,
        'parentId' => $parentId,
        'columnIndex' => $columnIndex,
    ], 'block-editor-'.$blockId)
</div>
