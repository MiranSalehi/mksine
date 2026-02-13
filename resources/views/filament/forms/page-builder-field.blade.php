<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div class="h-full min-h-[calc(100vh-8rem)]"
        x-data="{
            state: $wire.$entangle('{{ $getStatePath() }}').live,
            init() {
                const statePath = '{{ $getStatePath() }}';
                
                Livewire.on('builder-value-changed', (event) => {
                    if (event.blocks !== undefined) {
                        this.state = event.blocks;
                        $wire.set(statePath, event.blocks);
                    }
                });
            }
        }"
        wire:ignore
    >
        @livewire('mksine::page-builder', ['value' => $getState() ?? []], key($getStatePath()))
    </div>
</x-dynamic-component>
