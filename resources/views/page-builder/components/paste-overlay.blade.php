@if($showPasteModal)
    <div
        class="fixed inset-0 z-[100] overflow-y-auto"
        x-data
        x-show="true"
        role="dialog"
        aria-modal="true"
        aria-labelledby="paste-modal-title"
    >
        <div
            class="fixed inset-0 bg-gray-900/60 dark:bg-gray-950/80 backdrop-blur-sm"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            wire:click="closePasteModal"
        ></div>
        <div class="fixed inset-0 z-10 flex items-center justify-center overflow-y-auto p-4">
            <div
                class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-800"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.stop
            >
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <h3 id="paste-modal-title" class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('mksine::page_builder.paste_component') }}</h3>
                    <button type="button" wire:click="closePasteModal" class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300" aria-label="{{ __('mksine::page_builder.close') }}">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                    </button>
                </div>
                <div class="p-6">
                    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">{{ __('mksine::page_builder.paste_box_help') }}</p>
                    <textarea
                        wire:model="pasteText"
                        rows="6"
                        class="w-full rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 font-mono text-sm text-gray-900 shadow-sm transition-shadow focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-violet-500 dark:focus:ring-violet-500/20"
                        placeholder="Paste JSON: id, type, data"
                    ></textarea>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                    <button type="button" wire:click="closePasteModal" class="rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                        {{ __('mksine::page_builder.cancel') }}
                    </button>
                    <button type="button" wire:click="submitPasteModal" class="rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-4 py-2.5 text-sm font-medium text-white shadow-lg shadow-violet-500/30 transition-all hover:shadow-violet-500/40">
                        {{ __('mksine::page_builder.insert') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
