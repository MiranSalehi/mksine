<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                {{ __('mksine::updater.core_current_version_heading') }}
            </x-slot>

            <div class="text-sm text-gray-700 dark:text-gray-200 space-y-2">
                <p>
                    <strong>{{ __('mksine::updater.core_current_version_label') }}:</strong>
                    <code>{{ $this->getCurrentVersion() }}</code>
                </p>
                <p class="text-gray-500 dark:text-gray-400">
                    {{ __('mksine::updater.core_composer_intro') }}
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                {{ __('mksine::updater.core_path_composer_heading') }}
            </x-slot>

            <div class="text-sm space-y-3 text-gray-700 dark:text-gray-200">
                <p>{{ __('mksine::updater.core_path_composer_body') }}</p>
                <pre class="bg-gray-100 dark:bg-gray-800 rounded px-3 py-2 text-xs overflow-x-auto"><code>composer update miran/mksine
php artisan vendor:publish --tag=mksine-migrations
php artisan migrate --force</code></pre>
                <p class="text-gray-500 dark:text-gray-400">
                    {{ __('mksine::updater.core_cli_equivalent') }}
                </p>
                <pre class="bg-gray-100 dark:bg-gray-800 rounded px-3 py-2 text-xs overflow-x-auto"><code>php artisan mksine:update --force</code></pre>
                @if ($this->composerIsAvailable() && $this->consoleTerminalUrl())
                    <p class="text-amber-700 dark:text-amber-300">
                        {{ __('mksine::updater.core_console_warning') }}
                    </p>
                    <p>
                        <a href="{{ $this->consoleTerminalUrl() }}" class="text-primary-600 dark:text-primary-400 underline">
                            {{ __('mksine::updater.core_console_link') }}
                        </a>
                    </p>
                @elseif (! $this->composerIsAvailable())
                    <p class="text-amber-700 dark:text-amber-300">
                        {{ __('mksine::updater.core_composer_missing') }}
                    </p>
                @endif
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                {{ __('mksine::updater.core_path_offline_heading') }}
            </x-slot>

            <div class="text-sm space-y-3 text-gray-700 dark:text-gray-200">
                <p>{{ __('mksine::updater.core_path_offline_body') }}</p>
                <pre class="bg-gray-100 dark:bg-gray-800 rounded px-3 py-2 text-xs overflow-x-auto"><code>composer.json
composer.lock
vendor/</code></pre>
                <p class="text-gray-500 dark:text-gray-400">
                    {{ __('mksine::updater.core_path_repo_note') }}
                </p>
                <p class="text-gray-500 dark:text-gray-400">
                    {{ __('mksine::updater.core_release_archive_note') }}
                </p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
