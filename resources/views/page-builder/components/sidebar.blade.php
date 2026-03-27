<div
    class="shrink-0 overflow-hidden transition-[width] duration-300 ease-out"
    x-data="{ open: @entangle('showComponentPanel') }"
    :class="open ? 'w-80' : 'w-0'"
>
    <aside class="flex h-full min-h-0 w-80 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lg ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-800/95 dark:ring-white/5" role="complementary" aria-label="{{ __('mksine::page_builder.components') }}">
        <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-violet-500/20 to-fuchsia-500/20">
                    <x-heroicon-o-squares-2x2 class="h-4 w-4 text-violet-600 dark:text-violet-400" />
                </div>
                <h4 class="font-semibold text-gray-900 dark:text-white">{{ __('mksine::page_builder.components') }}</h4>
            </div>
            <button type="button" @click="open = false" wire:click="closeComponentPanel" class="rounded-lg p-2 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-300" title="{{ __('mksine::page_builder.close') }}" aria-label="{{ __('mksine::page_builder.close') }}">
                <x-heroicon-o-x-mark class="h-5 w-5" />
            </button>
        </div>
        <div class="flex-1 space-y-6 overflow-y-auto p-4" aria-live="polite">
            @foreach($categoryDisplayMeta as $category => $meta)
                <section>
                    <div class="mb-2.5 flex items-center gap-2">
                        @if(!empty($meta['icon']))
                            <x-dynamic-component :component="$meta['icon']" class="h-4 w-4 shrink-0 text-violet-500 dark:text-violet-400" aria-hidden="true" />
                        @endif
                        <h5 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $meta['name'] ?? $category }}</h5>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($this->components[$category] ?? [] as $info)
                            <button
                                type="button"
                                wire:click="addBlock('{{ $info['type'] }}', {{ $insertAtPosition ?? 'null' }}, {{ $insertInParent ? "'{$insertInParent}'" : 'null' }}, {{ $insertInColumn !== null ? $insertInColumn : 'null' }})"
                                wire:loading.attr="disabled"
                                class="flex min-w-0 flex-col items-center gap-2 rounded-xl border border-gray-200 bg-white p-3 text-center transition-all duration-200 hover:border-violet-300 hover:shadow-md hover:shadow-violet-500/10 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-violet-600 dark:hover:shadow-violet-500/5"
                            >
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-violet-100 dark:bg-violet-900/30">
                                    <x-dynamic-component :component="$info['icon']" class="h-5 w-5 text-violet-600 dark:text-violet-400" aria-hidden="true" />
                                </div>
                                <span class="w-full truncate text-xs font-medium text-gray-700 dark:text-gray-300">{{ $info['name'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </aside>
</div>
