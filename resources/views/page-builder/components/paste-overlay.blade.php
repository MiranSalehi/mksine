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
            class="fixed inset-0 bg-zinc-950/50 backdrop-blur-[3px]"
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
                class="relative w-full max-w-md overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-[0_24px_64px_-12px_rgb(0_0_0/0.2)] dark:border-white/[0.07] dark:bg-zinc-900 dark:shadow-[0_24px_64px_-12px_rgb(0_0_0/0.7)]"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-[0.97]"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-[0.97]"
                @click.stop
            >
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-zinc-100 bg-zinc-50 px-5 py-4 dark:border-white/[0.06] dark:bg-zinc-950/60">
                    <h3 id="paste-modal-title" class="text-[15px] font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">
                        {{ __('mksine::page_builder.paste_component') }}
                    </h3>
                    <button
                        type="button"
                        wire:click="closePasteModal"
                        class="flex h-7 w-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-zinc-200 hover:text-zinc-700 dark:text-zinc-500 dark:hover:bg-white/[0.08] dark:hover:text-zinc-300"
                        aria-label="{{ __('mksine::page_builder.close') }}"
                    >
                        <x-heroicon-o-x-mark class="h-4 w-4" />
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-5 py-4">
                    <p class="mb-3 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('mksine::page_builder.paste_box_help') }}
                    </p>
                    <textarea
                        wire:model="pasteText"
                        rows="6"
                        class="w-full rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 font-mono text-xs text-zinc-800 shadow-inner outline-none transition focus:border-violet-400 focus:ring-2 focus:ring-violet-400/20 dark:border-white/[0.08] dark:bg-zinc-950 dark:text-zinc-200 dark:focus:border-violet-500 dark:focus:ring-violet-500/20"
                        placeholder="Paste JSON: id, type, data"
                    ></textarea>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-2.5 border-t border-zinc-100 bg-zinc-50 px-5 py-3.5 dark:border-white/[0.06] dark:bg-zinc-950/60">
                    <button
                        type="button"
                        wire:click="closePasteModal"
                        class="rounded-lg border border-zinc-200 bg-white px-3.5 py-2 text-xs font-semibold text-zinc-700 transition-colors hover:bg-zinc-50 dark:border-white/[0.08] dark:bg-white/[0.04] dark:text-zinc-300 dark:hover:bg-white/[0.07]"
                    >{{ __('mksine::page_builder.cancel') }}</button>
                    <button
                        type="button"
                        wire:click="submitPasteModal"
                        class="rounded-lg bg-violet-600 px-3.5 py-2 text-xs font-semibold text-white shadow-[0_2px_6px_0_rgb(124_58_237/0.3)] transition-colors hover:bg-violet-700"
                    >{{ __('mksine::page_builder.insert') }}</button>
                </div>
            </div>
        </div>
    </div>
@endif
