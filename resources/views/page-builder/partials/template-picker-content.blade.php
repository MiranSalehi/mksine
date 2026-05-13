<p class="mb-3 text-sm text-zinc-500 dark:text-zinc-400">
    {{ __('mksine::page_builder.start_from_prebuilt_layout') }}
</p>

<div class="mb-4 flex items-start gap-2.5 rounded-xl border border-amber-200/80 bg-amber-50 px-4 py-3 text-xs text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
    <x-heroicon-o-exclamation-triangle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
    <span>{{ __('mksine::page_builder.template_warning_replace') }}</span>
</div>

<div class="max-h-[60vh] overflow-y-auto">
    <div class="space-y-5">
        @foreach($templatesByCategory as $category => $templates)
            <section>
                @if(is_string($category) && $category !== '')
                    <p class="mb-2.5 px-0.5 text-[10px] font-bold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
                        {{ $category }}
                    </p>
                @endif
                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($templates as $key => $template)
                        <button
                            type="button"
                            wire:click="loadTemplate('{{ $key }}')"
                            class="group flex flex-col items-start rounded-xl border border-zinc-200/80 bg-white p-4 text-left shadow-[0_1px_3px_0_rgb(0_0_0/0.05)] transition-all duration-150 hover:border-violet-200 hover:shadow-[0_4px_12px_0_rgb(124_58_237/0.1)] dark:border-white/[0.07] dark:bg-zinc-900 dark:shadow-none dark:hover:border-violet-500/30"
                        >
                            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-[10px] bg-zinc-100 text-zinc-500 transition-colors group-hover:bg-violet-50 group-hover:text-violet-600 dark:bg-white/[0.06] dark:text-zinc-400 dark:group-hover:bg-violet-500/10 dark:group-hover:text-violet-400">
                                <x-heroicon-o-squares-2x2 class="h-5 w-5" />
                            </div>
                            <span class="text-[13px] font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $template['name'] ?? $key }}
                            </span>
                            @if(!empty($template['description']))
                                <p class="mt-1 line-clamp-2 text-[11px] text-zinc-400 dark:text-zinc-500">
                                    {{ $template['description'] }}
                                </p>
                            @endif
                            <span class="mt-2.5 inline-flex items-center gap-1 text-[11px] font-semibold text-violet-600 opacity-0 transition-opacity group-hover:opacity-100 dark:text-violet-400">
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
