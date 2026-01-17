<x-filament-panels::page>
    <div class="space-y-6">
        @if(empty($plugins))
            <x-filament::section>
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <x-heroicon-o-puzzle-piece class="w-16 h-16 text-gray-400 dark:text-gray-500 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ __('No plugins found') }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-md">
                        {{ __('Click "Upload Plugin" to upload a plugin ZIP file, or use the "Discover Plugins" button if you have manually placed plugins in the plugins/ directory.') }}
                    </p>
                </div>
            </x-filament::section>
        @else
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach($plugins as $plugin)
                    <x-filament::section class="relative">
                        <div class="space-y-3">
                            {{-- Header --}}
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                        {{ $plugin['name'] }}
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                        {{ $plugin['id'] }}
                                    </p>
                                </div>
                                <x-filament::badge :color="$this->getStatusColor($plugin['status'])">
                                    {{ $this->getStatusLabel($plugin['status']) }}
                                </x-filament::badge>
                            </div>

                            {{-- Info --}}
                            <div class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ __('Version') }}:</span>
                                    <x-filament::badge color="info" size="sm">
                                        v{{ $plugin['version'] }}
                                    </x-filament::badge>
                                </div>
                                @if($plugin['author'])
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">{{ __('Author') }}:</span>
                                        <span>{{ $plugin['author'] }}</span>
                                    </div>
                                @endif
                            </div>

                            @if($plugin['description'])
                                <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                                    {{ $plugin['description'] }}
                                </p>
                            @endif

                            {{-- Actions --}}
                            <div class="flex flex-wrap gap-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                @if($plugin['status'] === 'not_installed')
                                    <x-filament::button
                                        size="sm"
                                        color="info"
                                        icon="heroicon-o-arrow-down-tray"
                                        wire:click="installPlugin('{{ $plugin['id'] }}')"
                                        wire:loading.attr="disabled"
                                    >
                                        {{ __('Install') }}
                                    </x-filament::button>

                                    <x-filament::button
                                        size="sm"
                                        color="danger"
                                        icon="heroicon-o-trash"
                                        wire:click="deletePlugin('{{ $plugin['id'] }}')"
                                        wire:loading.attr="disabled"
                                        wire:confirm="{{ __('Are you sure you want to permanently delete this plugin? This will remove all plugin files from the server.') }}"
                                    >
                                        {{ __('Delete') }}
                                    </x-filament::button>
                                @endif

                                @if(in_array($plugin['status'], ['installed', 'inactive', 'boot_failed']))
                                    <x-filament::button
                                        size="sm"
                                        color="success"
                                        icon="heroicon-o-play"
                                        wire:click="activatePlugin('{{ $plugin['id'] }}')"
                                        wire:loading.attr="disabled"
                                    >
                                        {{ __('Activate') }}
                                    </x-filament::button>
                                @endif

                                @if($plugin['status'] === 'active')
                                    <x-filament::button
                                        size="sm"
                                        color="warning"
                                        icon="heroicon-o-pause"
                                        wire:click="deactivatePlugin('{{ $plugin['id'] }}')"
                                        wire:loading.attr="disabled"
                                    >
                                        {{ __('Deactivate') }}
                                    </x-filament::button>
                                @endif

                                @if(in_array($plugin['status'], ['installed', 'inactive', 'boot_failed']))
                                    <x-filament::button
                                        size="sm"
                                        color="danger"
                                        icon="heroicon-o-trash"
                                        wire:click="uninstallPlugin('{{ $plugin['id'] }}')"
                                        wire:loading.attr="disabled"
                                        wire:confirm="{{ __('Are you sure you want to uninstall this plugin?') }}"
                                    >
                                        {{ __('Uninstall') }}
                                    </x-filament::button>
                                @endif
                            </div>

                            @if($plugin['status'] === 'boot_failed')
                                <div class="mt-2 p-2 bg-danger-50 dark:bg-danger-900/20 rounded-lg">
                                    <p class="text-xs text-danger-600 dark:text-danger-400">
                                        <x-heroicon-o-exclamation-triangle class="w-4 h-4 inline-block mr-1" />
                                        {{ __('This plugin failed during boot and was auto-disabled.') }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </x-filament::section>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>

