<x-filament-panels::page>
    <div class="space-y-6">
        @if(empty($plugins))
            <x-filament::section>
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <div class="relative mb-6">
                        <div class="absolute inset-0 rounded-2xl bg-primary-500/10 blur-2xl"></div>
                        <div class="relative flex h-24 w-24 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-500/20 to-primary-600/10 ring-1 ring-primary-500/20 dark:from-primary-500/30 dark:to-primary-600/20">
                            <x-heroicon-o-puzzle-piece class="w-12 h-12 text-primary-500 dark:text-primary-400" />
                        </div>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ __('mksine::plugins.no_plugins_found') }}
                    </h3>
                    <p class="mt-2 max-w-md text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                        {{ __('mksine::plugins.no_plugins_found_help') }}
                    </p>
                </div>
            </x-filament::section>
        @else
            <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($plugins as $plugin)
                    @php
                        $statusColors = [
                            'active' => 'from-emerald-500/20 to-teal-500/10 ring-emerald-500/20 dark:from-emerald-500/30 dark:to-teal-600/20',
                            'inactive' => 'from-amber-500/20 to-orange-500/10 ring-amber-500/20 dark:from-amber-500/30 dark:to-orange-600/20',
                            'installed' => 'from-blue-500/20 to-indigo-500/10 ring-blue-500/20 dark:from-blue-500/30 dark:to-indigo-600/20',
                            'not_installed' => 'from-gray-400/20 to-gray-500/10 ring-gray-400/20 dark:from-gray-500/30 dark:to-gray-600/20',
                            'boot_failed' => 'from-danger-500/20 to-red-600/10 ring-danger-500/20 dark:from-danger-500/30 dark:to-red-700/20',
                        ];
                        $headerClass = $statusColors[$plugin['status']] ?? $statusColors['not_installed'];
                    @endphp
                    <div
                        class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm ring-1 ring-black/5 transition-all duration-200 hover:shadow-lg hover:ring-gray-300/50 dark:border-gray-700 dark:bg-gray-800/50 dark:ring-white/5 dark:hover:border-gray-600 dark:hover:ring-white/10"
                    >
                        {{-- Header area with gradient --}}
                        <div class="relative flex h-28 items-center justify-center bg-gradient-to-br {{ $headerClass }} px-4">
                            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-white/40 to-transparent dark:from-white/5 dark:to-transparent"></div>
                            <div class="relative flex h-16 w-16 items-center justify-center rounded-xl bg-white/60 shadow-lg backdrop-blur-sm dark:bg-white/10">
                                <x-heroicon-o-puzzle-piece class="w-9 h-9 text-gray-600 dark:text-gray-300" />
                            </div>

                            {{-- Status badge --}}
                            <div class="absolute end-3 top-3 rtl:end-auto rtl:left-3">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium shadow-sm
                                        @if($plugin['status'] === 'active') bg-emerald-100 text-emerald-800 dark:bg-emerald-500/30 dark:text-emerald-200
                                        @elseif($plugin['status'] === 'inactive') bg-amber-100 text-amber-800 dark:bg-amber-500/30 dark:text-amber-200
                                        @elseif($plugin['status'] === 'installed') bg-blue-100 text-blue-800 dark:bg-blue-500/30 dark:text-blue-200
                                        @elseif($plugin['status'] === 'boot_failed') bg-danger-100 text-danger-800 dark:bg-danger-500/30 dark:text-danger-200
                                        @else bg-gray-100 text-gray-700 dark:bg-gray-600/50 dark:text-gray-300 @endif"
                                >
                                    {{ $this->getStatusLabel($plugin['status']) }}
                                </span>
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="p-4">
                            <div class="mb-3">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $plugin['name'] }}
                                </h3>
                                <p class="mt-0.5 font-mono text-xs text-gray-500 dark:text-gray-400">
                                    {{ $plugin['id'] }}
                                </p>
                            </div>

                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700/80 dark:text-gray-300">
                                    v{{ $plugin['version'] }}
                                </span>
                                @if($plugin['author'])
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $plugin['author'] }}
                                    </span>
                                @endif
                            </div>

                            @if($plugin['description'])
                                <p class="mb-4 line-clamp-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                                    {{ $plugin['description'] }}
                                </p>
                            @endif

                            @if($plugin['status'] === 'boot_failed')
                                <div class="mb-4 flex items-start gap-2 rounded-lg border border-danger-200 bg-danger-50 px-3 py-2 dark:border-danger-900/50 dark:bg-danger-500/10">
                                    <x-heroicon-o-exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0 text-danger-500 dark:text-danger-400" />
                                    <p class="text-xs text-danger-700 dark:text-danger-300">
                                        {{ __('mksine::plugins.boot_failed_message') }}
                                    </p>
                                </div>
                            @endif

                            {{-- Actions --}}
                            <div class="flex flex-wrap items-center gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                                @if($plugin['status'] === 'not_installed')
                                    <x-filament::button
                                        size="sm"
                                        color="info"
                                        icon="heroicon-o-arrow-down-tray"
                                        wire:click="installPlugin('{{ $plugin['id'] }}')"
                                        wire:loading.attr="disabled"
                                        class="font-medium"
                                    >
                                        {{ __('mksine::plugins.install') }}
                                    </x-filament::button>

                                    <x-filament::button
                                        size="sm"
                                        color="danger"
                                        icon="heroicon-o-trash"
                                        wire:click="deletePlugin('{{ $plugin['id'] }}')"
                                        wire:loading.attr="disabled"
                                        wire:confirm="{{ __('mksine::plugins.delete_confirm') }}"
                                    >
                                        {{ __('mksine::plugins.delete') }}
                                    </x-filament::button>
                                @endif

                                @if(in_array($plugin['status'], ['installed', 'inactive', 'boot_failed']))
                                    <x-filament::button
                                        size="sm"
                                        color="success"
                                        icon="heroicon-o-play"
                                        wire:click="activatePlugin('{{ $plugin['id'] }}')"
                                        wire:loading.attr="disabled"
                                        class="font-medium"
                                    >
                                        {{ __('mksine::plugins.activate') }}
                                    </x-filament::button>

                                    <x-filament::button
                                        size="sm"
                                        color="danger"
                                        icon="heroicon-o-trash"
                                        wire:click="uninstallPlugin('{{ $plugin['id'] }}')"
                                        wire:loading.attr="disabled"
                                        wire:confirm="{{ __('mksine::plugins.uninstall_confirm') }}"
                                    >
                                        {{ __('mksine::plugins.uninstall') }}
                                    </x-filament::button>
                                @endif

                                @if($plugin['status'] === 'active')
                                    <x-filament::button
                                        size="sm"
                                        color="warning"
                                        icon="heroicon-o-pause"
                                        wire:click="deactivatePlugin('{{ $plugin['id'] }}')"
                                        wire:loading.attr="disabled"
                                        class="font-medium"
                                    >
                                        {{ __('mksine::plugins.deactivate') }}
                                    </x-filament::button>
                                @endif

                                <x-filament::button
                                    size="sm"
                                    color="gray"
                                    icon="heroicon-o-document-text"
                                    wire:click="openPluginLog('{{ $plugin['id'] }}')"
                                    wire:loading.attr="disabled"
                                    class="ms-auto rtl:ms-0 rtl:me-auto"
                                >
                                    {{ __('mksine::plugins.view_log') }}
                                </x-filament::button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Plugin log modal --}}
    <x-filament::modal
        id="plugin-log-modal"
        :heading="__('mksine::plugins.plugin_log') . ': ' . $pluginLogPluginName"
        width="4xl"
        sticky-header
        sticky-footer
    >
        <div class="max-h-[60vh] overflow-y-auto">
            <pre class="min-h-[200px] whitespace-pre-wrap break-words rounded-xl bg-gray-100 px-4 py-4 font-mono text-xs text-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ $pluginLogContent }}</pre>
        </div>

        <x-slot:footerActions>
            @if($pluginLogPluginId !== '' && $this->hasPluginLog($pluginLogPluginId))
                <x-filament::button
                    color="danger"
                    icon="heroicon-o-trash"
                    wire:click="clearPluginLog"
                    wire:loading.attr="disabled"
                    wire:confirm="{{ __('mksine::plugins.clear_log_confirm') }}"
                >
                    {{ __('mksine::plugins.clear_log') }}
                </x-filament::button>
            @endif
            <x-filament::button
                color="gray"
                wire:click="closePluginLogModal"
            >
                {{ __('mksine::plugins.close') }}
            </x-filament::button>
        </x-slot:footerActions>
    </x-filament::modal>
</x-filament-panels::page>
