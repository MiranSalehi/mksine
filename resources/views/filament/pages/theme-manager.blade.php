<x-filament-panels::page>
    <div class="mksine-theme-manager-root space-y-6">
        {{-- Themes Grid --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($this->getThemes() as $theme)
                @php
                    $screenshotUrl = app(\Miran\Mksine\Core\Theme\ThemeManager::class)->getScreenshotUrl($theme);
                    $isActive = $theme->identifier === $this->getActiveThemeIdentifier();
                @endphp
                <div
                    data-theme-card
                    class="group relative overflow-hidden rounded-xl border bg-white shadow-sm ring-1 ring-black/5 transition-all duration-200 hover:shadow-lg hover:ring-gray-300/50 dark:bg-gray-800/50 dark:ring-white/5 dark:hover:border-gray-600 dark:hover:ring-white/10
                        {{ $isActive
                            ? 'border-2 border-primary-500 ring-2 ring-primary-500/20 dark:border-primary-500'
                            : 'border-gray-200 dark:border-gray-700' }}"
                >
                    {{-- Theme Screenshot / Visual Header --}}
                    <div class="relative aspect-video overflow-hidden bg-gray-100 dark:bg-gray-900">
                        @if($screenshotUrl)
                            <img
                                src="{{ $screenshotUrl }}"
                                alt="{{ $theme->name }}"
                                class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-[1.02]"
                            >
                            <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent dark:from-black/60"></div>
                        @else
                            <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-violet-500/20 via-primary-500/10 to-fuchsia-500/20 dark:from-violet-500/30 dark:to-fuchsia-600/20">
                                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_start,_var(--tw-gradient-stops))] from-white/50 to-transparent dark:from-white/10 dark:to-transparent"></div>
                                <div class="relative flex h-20 w-20 items-center justify-center rounded-xl bg-white/70 shadow-lg backdrop-blur-sm dark:bg-white/15">
                                    <x-heroicon-o-paint-brush class="h-10 w-10 text-gray-600 dark:text-gray-300" />
                                </div>
                            </div>
                        @endif

                        {{-- Active Badge --}}
                        @if($isActive)
                            <div class="absolute end-3 top-3 rtl:end-auto rtl:left-3">
                                <span class="inline-flex items-center gap-1 rounded-full bg-primary-500 px-2.5 py-1 text-xs font-medium text-white shadow-lg">
                                    <x-heroicon-s-check class="h-3.5 w-3.5" />
                                    {{ __('mksine::themes.active') }}
                                </span>
                            </div>
                        @endif

                        {{-- Source Badge --}}
                        <div class="absolute start-3 top-3 rtl:start-auto rtl:right-3">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium shadow-sm
                                    {{ $theme->isPackageTheme()
                                        ? 'bg-blue-100 text-blue-800 dark:bg-blue-500/30 dark:text-blue-200'
                                        : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/30 dark:text-emerald-200' }}"
                            >
                                {{ $theme->isPackageTheme() ? __('mksine::themes.package') : __('mksine::themes.project') }}
                            </span>
                        </div>
                    </div>

                    {{-- Theme Info --}}
                    <div class="p-4">
                        <div class="mb-3">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $theme->name }}
                            </h3>
                            <p class="mt-0.5 flex flex-wrap items-center gap-x-2 text-sm text-gray-500 dark:text-gray-400">
                                <span class="font-medium">v{{ $theme->version }}</span>
                                @if($theme->author)
                                    <span>&middot;</span>
                                    <span>{{ $theme->author }}</span>
                                @endif
                            </p>
                        </div>

                        @if($theme->description)
                            <p class="mb-4 line-clamp-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                                {{ $theme->description }}
                            </p>
                        @endif

                        {{-- Theme Meta --}}
                        @if($theme->hasAssets())
                            <div class="mb-4">
                                <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700/80 dark:text-gray-300">
                                    <x-heroicon-s-check-circle class="h-3.5 w-3.5 text-emerald-500 dark:text-emerald-400" />
                                    {{ __('mksine::themes.assets_ready') }}
                                </span>
                            </div>
                        @endif

                        {{-- Actions --}}
                        <div class="flex flex-col gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                            <div class="flex gap-2">
                                @if(!$isActive)
                                    <x-filament::button
                                        wire:click="activateTheme('{{ $theme->identifier }}')"
                                        wire:loading.attr="disabled"
                                        size="sm"
                                        class="flex-1 font-medium"
                                    >
                                        {{ __('mksine::themes.activate') }}
                                    </x-filament::button>

                                    @if($theme->isProjectTheme())
                                        <x-filament::button
                                            wire:click="deleteTheme('{{ $theme->identifier }}')"
                                            wire:loading.attr="disabled"
                                            wire:confirm="{{ __('mksine::themes.delete_theme_confirm') }}"
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
                                        {{ __('mksine::themes.currently_active') }}
                                    </x-filament::button>
                                @endif
                            </div>
                            <x-filament::button
                                wire:click="mountAction('customCssJs', { themeIdentifier: '{{ $theme->identifier }}' })"
                                size="sm"
                                color="gray"
                                icon="heroicon-o-code-bracket-square"
                                class="w-full"
                            >
                                {{ __('mksine::themes.custom_css_js') }}
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full">
                    <x-filament::section>
                        <div class="flex flex-col items-center justify-center py-16 text-center">
                            <div class="relative mb-6">
                                <div class="absolute inset-0 rounded-2xl bg-primary-500/10 blur-2xl"></div>
                                <div class="relative flex h-24 w-24 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500/20 via-primary-500/20 to-fuchsia-500/20 ring-1 ring-primary-500/20 dark:from-violet-500/30 dark:to-fuchsia-600/20">
                                    <x-heroicon-o-paint-brush class="h-12 w-12 text-primary-500 dark:text-primary-400" />
                                </div>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                {{ __('mksine::themes.no_themes_found') }}
                            </h3>
                            <p class="mt-2 max-w-md text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                                {{ __('mksine::themes.no_themes_found_help') }}
                            </p>
                        </div>
                    </x-filament::section>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
