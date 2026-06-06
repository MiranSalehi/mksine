<x-filament-panels::page>
    @php
        $stats = $this->getPluginStats();
    @endphp

    <div class="space-y-6">
        @if (empty($plugins))
            <x-filament::section>
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <div class="relative mb-6">
                        <div class="absolute inset-0 rounded-2xl bg-primary-500/10 blur-2xl"></div>
                        <div class="relative flex h-24 w-24 items-center justify-center rounded-2xl bg-gradient-to-br from-primary-500/20 to-primary-600/10 ring-1 ring-primary-500/20 dark:from-primary-500/30 dark:to-primary-600/20">
                            <x-heroicon-o-puzzle-piece class="h-12 w-12 text-primary-500 dark:text-primary-400" />
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
            {{-- Summary --}}
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-900/40 dark:ring-white/5">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('mksine::plugins.stats_total') }}
                    </p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                </div>
                <div class="rounded-xl border border-emerald-200/60 bg-gradient-to-br from-emerald-50/80 to-white p-4 shadow-sm ring-1 ring-emerald-500/10 dark:border-emerald-500/20 dark:from-emerald-500/10 dark:to-gray-900/40 dark:ring-emerald-500/20">
                    <p class="text-xs font-medium uppercase tracking-wide text-emerald-700/80 dark:text-emerald-300/80">
                        {{ __('mksine::plugins.stats_active') }}
                    </p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-emerald-800 dark:text-emerald-200">{{ $stats['active'] }}</p>
                </div>
                <div class="rounded-xl border border-amber-200/60 bg-gradient-to-br from-amber-50/80 to-white p-4 shadow-sm ring-1 ring-amber-500/10 dark:border-amber-500/20 dark:from-amber-500/10 dark:to-gray-900/40 dark:ring-amber-500/20">
                    <p class="text-xs font-medium uppercase tracking-wide text-amber-800/80 dark:text-amber-200/80">
                        {{ __('mksine::plugins.stats_inactive') }}
                    </p>
                    <p class="mt-1 text-2xl font-bold tabular-nums text-amber-900 dark:text-amber-100">{{ $stats['inactive'] }}</p>
                </div>
                <div @class([
                    'rounded-xl border p-4 shadow-sm ring-1',
                    'border-danger-200/60 bg-gradient-to-br from-danger-50/80 to-white ring-danger-500/10 dark:border-danger-500/20 dark:from-danger-500/10 dark:to-gray-900/40 dark:ring-danger-500/20' => $stats['attention'] > 0,
                    'border-gray-200/80 bg-white ring-black/5 dark:border-gray-700 dark:bg-gray-900/40 dark:ring-white/5' => $stats['attention'] === 0,
                ])>
                    <p @class([
                        'text-xs font-medium uppercase tracking-wide',
                        'text-danger-700/80 dark:text-danger-300/80' => $stats['attention'] > 0,
                        'text-gray-500 dark:text-gray-400' => $stats['attention'] === 0,
                    ])>
                        {{ __('mksine::plugins.stats_attention') }}
                    </p>
                    <p @class([
                        'mt-1 text-2xl font-bold tabular-nums',
                        'text-danger-800 dark:text-danger-200' => $stats['attention'] > 0,
                        'text-gray-900 dark:text-white' => $stats['attention'] === 0,
                    ])>{{ $stats['attention'] }}</p>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-sm ring-1 ring-black/5 dark:border-gray-700 dark:bg-gray-900/30 dark:ring-white/5">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[56rem] border-collapse text-start">
                        <thead>
                            <tr class="border-b border-gray-200/80 bg-gray-50/90 dark:border-gray-700 dark:bg-gray-800/50">
                                <th scope="col" class="px-5 py-3.5 text-start text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ __('mksine::plugins.table_plugin') }}
                                </th>
                                <th scope="col" class="hidden px-4 py-3.5 text-start text-xs font-semibold uppercase tracking-wide text-gray-500 md:table-cell dark:text-gray-400">
                                    {{ __('mksine::plugins.version') }}
                                </th>
                                <th scope="col" class="hidden px-4 py-3.5 text-start text-xs font-semibold uppercase tracking-wide text-gray-500 lg:table-cell dark:text-gray-400">
                                    {{ __('mksine::plugins.author') }}
                                </th>
                                <th scope="col" class="px-4 py-3.5 text-start text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ __('mksine::plugins.table_status') }}
                                </th>
                                <th scope="col" class="px-5 py-3.5 text-end text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ __('mksine::plugins.table_actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($plugins as $plugin)
                                @php
                                    $pluginId = $plugin['id'];
                                    $status = $plugin['status'];
                                @endphp
                                <tr
                                    wire:key="plugin-row-{{ $pluginId }}"
                                    @class([
                                        'group transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-800/40',
                                        'bg-danger-500/[0.03] dark:bg-danger-500/[0.06]' => $status === 'boot_failed',
                                    ])
                                >
                                    <td class="px-5 py-4 align-top">
                                        <div class="flex items-start gap-3">
                                            <div @class([
                                                'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br ring-1 ring-inset ring-white/60 dark:ring-white/10',
                                                $this->getPluginIconGradientClasses($status),
                                            ])>
                                                <x-heroicon-o-puzzle-piece class="h-5 w-5 text-gray-600 dark:text-gray-300" />
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ $plugin['name'] }}
                                                </p>
                                                <p class="mt-0.5 font-mono text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $pluginId }}
                                                </p>
                                                @if ($plugin['description'])
                                                    <p class="mt-1.5 line-clamp-2 max-w-xl text-xs leading-relaxed text-gray-600 dark:text-gray-400">
                                                        {{ $plugin['description'] }}
                                                    </p>
                                                @endif
                                                @if ($status === 'boot_failed')
                                                    <p class="mt-2 flex items-start gap-1.5 text-xs text-danger-600 dark:text-danger-400">
                                                        <x-heroicon-o-exclamation-triangle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                                        <span>{{ __('mksine::plugins.boot_failed_message') }}</span>
                                                    </p>
                                                @endif
                                                <div class="mt-2 flex flex-wrap items-center gap-2 md:hidden">
                                                    <span class="rounded-md bg-gray-100 px-2 py-0.5 font-mono text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                                        v{{ $plugin['version'] }}
                                                    </span>
                                                    @if ($plugin['author'])
                                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $plugin['author'] }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden px-4 py-4 align-middle md:table-cell">
                                        <span class="inline-flex rounded-lg bg-gray-100 px-2.5 py-1 font-mono text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                            v{{ $plugin['version'] }}
                                        </span>
                                    </td>
                                    <td class="hidden px-4 py-4 align-middle text-sm text-gray-600 lg:table-cell dark:text-gray-400">
                                        {{ $plugin['author'] ?: '—' }}
                                    </td>
                                    <td class="px-4 py-4 align-middle">
                                        <span @class([
                                            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',
                                            $this->getStatusBadgeClasses($status),
                                        ])>
                                            <span @class(['h-1.5 w-1.5 shrink-0 rounded-full', $this->getStatusDotClasses($status)])></span>
                                            {{ $this->getStatusLabel($status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 align-middle">
                                        <div class="flex flex-wrap items-center justify-end gap-1.5">
                                            @if ($status === 'not_installed')
                                                <x-filament::button
                                                    size="sm"
                                                    color="info"
                                                    icon="heroicon-o-arrow-down-tray"
                                                    wire:click="installPlugin('{{ $pluginId }}')"
                                                    wire:loading.attr="disabled"
                                                >
                                                    {{ __('mksine::plugins.install') }}
                                                </x-filament::button>
                                                <x-filament::button
                                                    size="sm"
                                                    color="danger"
                                                    icon="heroicon-o-trash"
                                                    wire:click="deletePlugin('{{ $pluginId }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:confirm="{{ __('mksine::plugins.delete_confirm') }}"
                                                >
                                                    {{ __('mksine::plugins.delete') }}
                                                </x-filament::button>
                                            @endif

                                            @if (in_array($status, ['installed', 'inactive', 'boot_failed'], true))
                                                <x-filament::button
                                                    size="sm"
                                                    color="success"
                                                    icon="heroicon-o-play"
                                                    wire:click="activatePlugin('{{ $pluginId }}')"
                                                    wire:loading.attr="disabled"
                                                >
                                                    {{ __('mksine::plugins.activate') }}
                                                </x-filament::button>
                                                <x-filament::button
                                                    size="sm"
                                                    color="danger"
                                                    outlined
                                                    icon="heroicon-o-trash"
                                                    wire:click="uninstallPlugin('{{ $pluginId }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:confirm="{{ __('mksine::plugins.uninstall_confirm') }}"
                                                >
                                                    {{ __('mksine::plugins.uninstall') }}
                                                </x-filament::button>
                                            @endif

                                            @if ($status === 'active')
                                                <x-filament::button
                                                    size="sm"
                                                    color="warning"
                                                    icon="heroicon-o-pause"
                                                    wire:click="deactivatePlugin('{{ $pluginId }}')"
                                                    wire:loading.attr="disabled"
                                                >
                                                    {{ __('mksine::plugins.deactivate') }}
                                                </x-filament::button>
                                            @endif

                                            <x-filament::button
                                                size="sm"
                                                color="gray"
                                                outlined
                                                icon="heroicon-o-document-text"
                                                wire:click="openPluginLog('{{ $pluginId }}')"
                                                wire:loading.attr="disabled"
                                            >
                                                {{ __('mksine::plugins.view_log') }}
                                            </x-filament::button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

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
            @if ($pluginLogPluginId !== '' && $this->hasPluginLog($pluginLogPluginId))
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
            <x-filament::button color="gray" wire:click="closePluginLogModal">
                {{ __('mksine::plugins.close') }}
            </x-filament::button>
        </x-slot:footerActions>
    </x-filament::modal>
</x-filament-panels::page>
