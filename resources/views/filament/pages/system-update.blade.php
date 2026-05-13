<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                {{ __('mksine::updater.core_current_version_heading') }}
            </x-slot>

            <div class="text-sm text-gray-700 dark:text-gray-200 space-y-2">
                <p><strong>{{ __('mksine::updater.core_current_version_label') }}:</strong>
                    <code>{{ $this->getCurrentVersion() }}</code></p>
                <p class="text-gray-500 dark:text-gray-400">
                    {{ __('mksine::updater.core_cli_recommended') }}
                </p>
                <pre class="bg-gray-100 dark:bg-gray-800 rounded px-3 py-2 text-xs overflow-x-auto"><code>php artisan mksine:update path/to/mksine-core.zip</code></pre>
            </div>
        </x-filament::section>

        @if ($lastResult)
            <x-filament::section>
                <x-slot name="heading">
                    @if ($lastResult->success)
                        {{ __('mksine::updater.result_success_heading') }}
                    @else
                        {{ __('mksine::updater.result_failure_heading') }}
                    @endif
                </x-slot>

                <div class="space-y-3 text-sm">
                    <div>
                        <strong>{{ __('mksine::updater.result_versions_label') }}:</strong>
                        <code>{{ $lastResult->fromVersion ?? '—' }}</code> →
                        <code>{{ $lastResult->toVersion ?? '—' }}</code>
                    </div>

                    @if (! empty($lastResult->steps))
                        <div>
                            <strong>{{ __('mksine::updater.result_steps_label') }}:</strong>
                            <ul class="list-disc list-inside mt-1 space-y-0.5">
                                @foreach ($lastResult->steps as $step)
                                    <li><code>{{ $step }}</code></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (! empty($lastResult->warnings))
                        <div>
                            <strong class="text-amber-700 dark:text-amber-300">
                                {{ __('mksine::updater.result_warnings_label') }}:
                            </strong>
                            <ul class="list-disc list-inside mt-1 space-y-0.5 text-amber-700 dark:text-amber-300">
                                @foreach ($lastResult->warnings as $w)
                                    <li>{{ $w }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (! $lastResult->success)
                        <div class="text-red-700 dark:text-red-300">
                            <strong>{{ __('mksine::updater.result_error_label') }}:</strong>
                            {{ $lastResult->errorMessage }}
                        </div>

                        @if ($lastResult->dbPossiblyDirty)
                            <div class="rounded border border-red-300 bg-red-50 dark:bg-red-900/30 dark:border-red-700 p-3 text-red-800 dark:text-red-100">
                                <strong>{{ __('mksine::updater.result_db_dirty_heading') }}</strong>
                                <p class="mt-1">{{ __('mksine::updater.result_db_dirty_body') }}</p>
                            </div>
                        @endif
                    @endif

                    <div class="text-xs text-gray-500">
                        {{ __('mksine::updater.result_log_label') }}:
                        <code>{{ $lastResult->logPath }}</code>
                    </div>

                    @if ($lastResult->backupPath)
                        <div class="text-xs text-gray-500">
                            {{ __('mksine::updater.result_backup_label') }}:
                            <code>{{ $lastResult->backupPath }}</code>
                        </div>
                    @endif
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
