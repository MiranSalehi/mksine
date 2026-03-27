<div id="blocks-list" class="space-y-3" x-ref="blocksList" role="list" aria-label="{{ __('mksine::page_builder.components_count', ['count' => count($blocks)]) }}">
    @foreach($blocks as $index => $block)
        @if($index === 0)
            @include('mksine::page-builder.components.insertion-point', ['position' => 0])
        @endif
        <div wire:key="block-{{ $block['id'] ?? $index }}">
            @include('mksine::page-builder.partials.block-item', ['block' => $block, 'index' => $index, 'parentId' => null, 'columnIndex' => null])
        </div>
        @include('mksine::page-builder.components.insertion-point', ['position' => $index + 1])
    @endforeach
    @if(empty($blocks))
        @include('mksine::page-builder.components.empty-state')
    @endif
</div>
