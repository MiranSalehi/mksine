@if($showPasteModal)
    <div
        class="fixed inset-0 z-[100] overflow-y-auto"
        x-data
        x-show="true"
        role="dialog"
        aria-modal="true"
        aria-labelledby="paste-modal-title"
    >
        {{-- Backdrop --}}
        <div
            class="fixed inset-0 bg-gray-950/50 backdrop-blur-[3px]"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            wire:click="closePasteModal"
        ></div>

        <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
            <div
                class="relative w-full max-w-md overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-[0_24px_64px_-12px_color-mix(in_oklab,black_20%,transparent)] dark:border-white/[0.07] dark:bg-gray-900 dark:shadow-[0_24px_64px_-12px_color-mix(in_oklab,black_70%,transparent)]"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-[0.97]"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-[0.97]"
                @click.stop
            >
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-5 py-4 dark:border-white/[0.06] dark:bg-gray-950/60">
                    <h3 id="paste-modal-title" class="text-[15px] font-semibold tracking-tight text-gray-900 dark:text-gray-50">
                        {{ __('mksine::page_builder.paste_component') }}
                    </h3>
                    <button
                        type="button"
                        wire:click="closePasteModal"
                        class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-700 dark:text-gray-500 dark:hover:bg-white/[0.08] dark:hover:text-gray-300"
                        aria-label="{{ __('mksine::page_builder.close') }}"
                    >
                        <x-heroicon-o-x-mark class="h-4 w-4" />
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-5 py-4">
                    <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('mksine::page_builder.paste_box_help') }}
                    </p>
                    <textarea
                        wire:model="pasteText"
                        rows="6"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 font-mono text-xs text-gray-800 shadow-inner outline-none transition focus:border-primary-400 focus:ring-2 focus:ring-primary-400/20 dark:border-white/[0.08] dark:bg-gray-950 dark:text-gray-200 dark:focus:border-primary-500 dark:focus:ring-primary-500/20"
                        placeholder="Paste JSON: id, type, data"
                    ></textarea>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-2.5 border-t border-gray-100 bg-gray-50 px-5 py-3.5 dark:border-white/[0.06] dark:bg-gray-950/60">
                    <button
                        type="button"
                        wire:click="closePasteModal"
                        class="rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-50 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-gray-300 dark:hover:bg-white/[0.07]"
                    >{{ __('mksine::page_builder.cancel') }}</button>
                    <button
                        type="button"
                        wire:click="submitPasteModal"
                        class="rounded-lg bg-primary-600 px-3.5 py-2 text-xs font-semibold text-white shadow-[0_2px_6px_0_color-mix(in_oklab,var(--primary-600)_30%,transparent)] transition-colors hover:bg-primary-700"
                    >{{ __('mksine::page_builder.insert') }}</button>
                </div>
            </div>
        </div>
    </div>
@endif
