<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        class="mksine-page-builder-field overflow-hidden rounded-xl border border-zinc-200/80 bg-white shadow-[0_1px_3px_0_rgb(0_0_0/0.05)] dark:border-white/[0.07] dark:bg-zinc-950"
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
        {{-- ignore.self: morph nested PageBuilder DOM; full wire:ignore breaks Sortable + nested columns --}}
        wire:ignore.self
    >
        @livewire('mksine::page-builder', ['value' => $getState() ?? []], key($getStatePath()))
    </div>
</x-dynamic-component>
