<x-filament-panels::page>
    @php($sections = $this->getSections())
    @php($problems = \Miran\Mksine\Core\Install\Health\SystemHealthReport::problemCount($sections))

    <x-filament::section>
        <div class="flex items-center gap-3 text-sm">
            @if ($problems === 0)
                <x-filament::badge color="success" :icon="\Filament\Support\Icons\Heroicon::OutlinedCheckCircle">{{ __('mksine::system_health.all_good') }}</x-filament::badge>
            @else
                <x-filament::badge color="danger" :icon="\Filament\Support\Icons\Heroicon::OutlinedExclamationTriangle">{{ trans_choice('mksine::system_health.problems', $problems, ['count' => $problems]) }}</x-filament::badge>
            @endif
        </div>
    </x-filament::section>

    <div class="grid gap-6 lg:grid-cols-2">
        @foreach ($sections as $sectionKey => $checks)
            <x-filament::section class="min-w-0">
                <x-slot name="heading">{{ __('mksine::system_health.sections.'.$sectionKey) }}</x-slot>

                <dl class="divide-y divide-gray-100 text-sm dark:divide-white/5">
                    @foreach ($checks as $check)
                        <div class="py-2.5" wire:key="health-{{ $check->key }}">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="font-medium text-gray-950 dark:text-white">{{ $check->label }}</dt>
                                <dd class="flex items-center gap-2 text-end">
                                    @if ($check->value !== null && $check->value !== '')
                                        <span class="break-all text-gray-600 dark:text-gray-300" dir="auto">{{ $check->value }}</span>
                                    @endif
                                    @if ($check->status !== \Miran\Mksine\Core\Install\Health\HealthCheck::INFO)
                                        <x-filament::badge :color="$check->color()" size="sm">{{ __('mksine::system_health.status.'.$check->status) }}</x-filament::badge>
                                    @endif
                                </dd>
                            </div>
                            @if ($check->hint)
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $check->hint }}</p>
                            @endif
                            @if ($check->command)
                                <pre dir="ltr" class="mt-2 whitespace-pre-wrap break-all rounded bg-gray-100 px-3 py-2 text-start text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-100"><code>{{ $check->command }}</code></pre>
                            @endif
                        </div>
                    @endforeach
                </dl>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
