<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm ring-1 ring-black/5 transition-shadow hover:ring-gray-300/50 dark:border-gray-700 dark:bg-gray-800/30 dark:ring-white/5 dark:hover:ring-white/10"
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
        <div class="min-h-[calc(100vh-10rem)] bg-gradient-to-b from-gray-50/50 to-white dark:from-gray-900/50 dark:to-gray-800/30">
            @livewire('mksine::page-builder', ['value' => $getState() ?? []], key($getStatePath()))
        </div>
    </div>
</x-dynamic-component>
