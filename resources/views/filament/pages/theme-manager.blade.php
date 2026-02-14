<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Themes Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($this->getThemes() as $theme)
                <div
                    class="relative bg-white dark:bg-gray-800 rounded-xl shadow-sm border-2 transition-all duration-200 overflow-hidden
                        {{ $theme->identifier === $this->getActiveThemeIdentifier()
                            ? 'border-primary-500 ring-2 ring-primary-500/20'
                            : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}"
                >
                    {{-- Theme Screenshot/Placeholder --}}
                    <div class="aspect-video bg-gray-100 dark:bg-gray-900 relative">
                        @php $screenshotUrl = app(\Miran\Mksine\Core\Theme\ThemeManager::class)->getScreenshotUrl($theme); @endphp
                        @if($screenshotUrl)
                            <img
                                src="{{ $screenshotUrl }}"
                                alt="{{ $theme->name }}"
                                class="w-full h-full object-cover"
                            >
                        @else
                            <div class="w-full h-full flex items-center justify-center">
                                <x-heroicon-o-photo class="w-16 h-16 text-gray-300 dark:text-gray-600" />
                            </div>
                        @endif

                        {{-- Active Badge --}}
                        @if($theme->identifier === $this->getActiveThemeIdentifier())
                            <div class="absolute top-3 right-3">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-primary-500 text-white">
                                    <x-heroicon-s-check class="w-3.5 h-3.5" />
                                    {{ __('Active') }}
                                </span>
                            </div>
                        @endif

                        {{-- Source Badge --}}
                        <div class="absolute top-3 left-3">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                {{ $theme->isPackageTheme()
                                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300'
                                    : 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300' }}">
                                {{ $theme->isPackageTheme() ? __('Package') : __('Project') }}
                            </span>
                        </div>
                    </div>

                    {{-- Theme Info --}}
                    <div class="p-4">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $theme->name }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    v{{ $theme->version }}
                                    @if($theme->author)
                                        &middot; {{ $theme->author }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if($theme->description)
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4 line-clamp-2">
                                {{ $theme->description }}
                            </p>
                        @endif

                        {{-- Theme Meta --}}
                        @if($theme->hasAssets())
                            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-4">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                    <x-heroicon-s-check-circle class="w-3 h-3" />
                                    {{ __('Assets Ready') }}
                                </span>
                            </div>
                        @endif

                        {{-- Actions --}}
                        <div class="flex gap-2">
                            @if($theme->identifier !== $this->getActiveThemeIdentifier())
                                <x-filament::button
                                    wire:click="activateTheme('{{ $theme->identifier }}')"
                                    wire:loading.attr="disabled"
                                    size="sm"
                                    class="flex-1"
                                >
                                    {{ __('Activate') }}
                                </x-filament::button>

                                {{-- Delete button for project themes only --}}
                                @if($theme->isProjectTheme())
                                    <x-filament::button
                                        wire:click="deleteTheme('{{ $theme->identifier }}')"
                                        wire:loading.attr="disabled"
                                        wire:confirm="{{ __('Are you sure you want to delete this theme? This will remove all theme files from the server.') }}"
                                        size="sm"
                                        color="danger"
                                        icon="heroicon-o-trash"
                                    >
                                    </x-filament::button>
                                @endif
                            @else
                                <x-filament::button
                                    disabled
                                    size="sm"
                                    color="gray"
                                    class="flex-1"
                                >
                                    {{ __('Currently Active') }}
                                </x-filament::button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <x-filament::section>
                        <div class="text-center py-12">
                            <x-heroicon-o-paint-brush class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-500 mb-4" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                {{ __('No themes found') }}
                            </h3>
                            <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                                {{ __('Click "Upload Theme" to upload a theme ZIP file, or use the "Discover Themes" button if you have manually placed themes in the themes directory.') }}
                            </p>
                        </div>
                    </x-filament::section>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
