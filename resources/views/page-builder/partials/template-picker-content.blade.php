<p class="mb-4 text-sm text-gray-500 dark:text-gray-400">{{ __('mksine::page_builder.start_from_prebuilt_layout') }}</p>
<div class="mb-4 flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50/80 px-4 py-3 text-xs text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
    <x-heroicon-o-exclamation-triangle class="h-4 w-4 shrink-0" />
    <span>{{ __('mksine::page_builder.template_warning_replace') }}</span>
</div>
<div class="max-h-[60vh] overflow-y-auto">
    <div class="space-y-6">
        @foreach($templatesByCategory as $category => $templates)
            <section>
                @if(is_string($category) && $category !== '')
                    <p class="mb-3 px-1 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $category }}</p>
                @endif
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($templates as $key => $template)
                        <button
                            type="button"
                            wire:click="loadTemplate('{{ $key }}')"
                            class="group relative flex flex-col items-start rounded-xl border border-gray-200/80 bg-white p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-violet-300 hover:shadow-lg hover:shadow-violet-500/10 dark:border-gray-700/80 dark:bg-gray-800/60 dark:hover:border-violet-600 dark:hover:shadow-violet-500/5"
                        >
                            <span class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 shadow-sm transition-all duration-200 group-hover:bg-gradient-to-br group-hover:from-violet-500 group-hover:to-fuchsia-500 group-hover:shadow-md group-hover:shadow-violet-500/25 dark:bg-gray-700/80">
                                <x-heroicon-o-squares-2x2 class="h-5 w-5 text-gray-500 transition-colors group-hover:text-white dark:text-gray-400" />
                            </span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $template['name'] ?? $key }}</span>
                            @if(!empty($template['description']))
                                <p class="mt-1 line-clamp-2 text-xs text-gray-500 dark:text-gray-400">{{ $template['description'] }}</p>
                            @endif
                            <span class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-violet-600 opacity-0 transition-opacity group-hover:opacity-100 dark:text-violet-400">
                                {{ __('mksine::page_builder.use_template_button') }}
                                <x-heroicon-o-arrow-right class="h-3.5 w-3.5" />
                            </span>
                        </button>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
