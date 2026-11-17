<p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
    {{ __('mksine::page_builder.start_from_prebuilt_layout') }}
</p>

<div class="mb-4 flex items-start gap-2.5 rounded-xl border border-warning-200/80 bg-warning-50 px-4 py-3 text-xs text-warning-800 dark:border-warning-500/20 dark:bg-warning-500/10 dark:text-warning-300">
    <x-heroicon-o-exclamation-triangle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
    <span>{{ __('mksine::page_builder.template_warning_replace') }}</span>
</div>

<div class="max-h-[60vh] overflow-y-auto">
    <div class="space-y-5">
        @foreach($templatesByCategory as $category => $templates)
            <section>
                @if(is_string($category) && $category !== '')
                    <p class="mb-2.5 px-0.5 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                        {{ $category }}
                    </p>
                @endif
                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($templates as $key => $template)
                        <button
                            type="button"
                            wire:click="loadTemplate('{{ $key }}')"
                            wire:loading.attr="disabled"
                            wire:target="loadTemplate('{{ $key }}')"
                            class="group relative flex flex-col items-start rounded-xl border border-gray-200/80 bg-white p-4 text-left shadow-[0_1px_3px_0_color-mix(in_oklab,black_5%,transparent)] transition-all duration-150 hover:border-primary-200 hover:shadow-[0_4px_12px_0_color-mix(in_oklab,var(--primary-600)_10%,transparent)] dark:border-white/[0.07] dark:bg-gray-900 dark:shadow-none dark:hover:border-primary-500/30"
                        >
                            <div class="relative mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 text-gray-500 transition-colors group-hover:bg-primary-50 group-hover:text-primary-600 dark:bg-white/[0.06] dark:text-gray-400 dark:group-hover:bg-primary-500/10 dark:group-hover:text-primary-400">
                                <span wire:loading.class="opacity-0" wire:target="loadTemplate('{{ $key }}')">
                                    <x-heroicon-o-squares-2x2 class="h-5 w-5" />
                                </span>
                                <span
                                    class="pointer-events-none absolute inset-0 hidden items-center justify-center"
                                    wire:loading.flex
                                    wire:target="loadTemplate('{{ $key }}')"
                                >
                                    <x-filament::loading-indicator class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                                </span>
                            </div>
                            <span class="text-[13px] font-semibold text-gray-900 dark:text-gray-100">
                                {{ $template['name'] ?? $key }}
                            </span>
                            @if(!empty($template['description']))
                                <p class="mt-1 line-clamp-2 text-[11px] text-gray-400 dark:text-gray-500">
                                    {{ $template['description'] }}
                                </p>
                            @endif
                            <span class="mt-2.5 inline-flex items-center gap-1 text-[11px] font-semibold text-primary-600 opacity-0 transition-opacity group-hover:opacity-100 dark:text-primary-400">
                                {{ __('mksine::page_builder.use_template_button') }}
                                <x-heroicon-o-arrow-right class="h-3 w-3" />
                            </span>
                        </button>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
