<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        class="mksine-page-builder-field overflow-hidden rounded-xl border border-gray-200/80 bg-white shadow-[0_1px_3px_0_color-mix(in_oklab,black_5%,transparent)] dark:border-white/[0.07] dark:bg-gray-950"
        x-data="{
            state: $wire.$entangle('{{ $getStatePath() }}'),
            _syncTimer: null,
            init() {
                const statePath = '{{ $getStatePath() }}';
                Livewire.on('builder-value-changed', (event) => {
                    if (event.blocks === undefined) {
                        return;
                    }
                    this.state = event.blocks;
                    clearTimeout(this._syncTimer);
                    this._syncTimer = setTimeout(() => {
                        $wire.set(statePath, event.blocks);
                    }, 150);
                });
            }
        }"
        {{-- ignore.self: morph nested PageBuilder DOM; full wire:ignore breaks Sortable + nested columns --}}
        wire:ignore.self
    >
        @livewire('mksine::page-builder', ['value' => $getState() ?? []], key($getStatePath()))
    </div>
</x-dynamic-component>
