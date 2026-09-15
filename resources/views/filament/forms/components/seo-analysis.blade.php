<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @php
        $result = $getSeoResult();
        $light = $result->trafficLight;
        $lightClasses = match ($light) {
            'good' => 'bg-success-100 text-success-700 dark:bg-success-500/15 dark:text-success-400',
            'ok' => 'bg-warning-100 text-warning-800 dark:bg-warning-500/15 dark:text-warning-400',
            default => 'bg-danger-100 text-danger-700 dark:bg-danger-500/15 dark:text-danger-400',
        };
        $dotClasses = match ($light) {
            'good' => 'bg-success-500',
            'ok' => 'bg-warning-500',
            default => 'bg-danger-500',
        };
    @endphp

    <div class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('mksine::seo.analysis_heading') }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('mksine::seo.advisory_hint') }}</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-medium {{ $lightClasses }}">
                <span class="inline-block h-2.5 w-2.5 rounded-full {{ $dotClasses }}"></span>
                <span>{{ __('mksine::seo.lights.'.$light) }}</span>
                <span>{{ $result->overall }}/100</span>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/60">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('mksine::seo.snippet_heading') }}</p>
            <p class="mt-2 text-lg font-medium text-blue-800 dark:text-blue-300">
                {{ $result->snippet['title'] !== '' ? $result->snippet['title'] : __('mksine::seo.snippet_untitled') }}
            </p>
            <p class="mt-0.5 text-sm text-success-700 dark:text-success-400" dir="ltr">
                {{ $result->snippet['url'] !== '' ? $result->snippet['url'] : 'example.com/…' }}
            </p>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                {{ $result->snippet['description'] !== '' ? $result->snippet['description'] : __('mksine::seo.snippet_no_description') }}
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            @foreach (['seo' => $result->seo, 'readability' => $result->readability] as $panel => $findings)
                <div class="flex flex-col gap-2">
                    <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('mksine::seo.panels.'.$panel) }}</p>
                    <ul class="flex flex-col gap-1.5">
                        @forelse ($findings as $finding)
                            @php
                                $itemClasses = match ($finding->status) {
                                    'good' => 'text-success-700 dark:text-success-400',
                                    'ok' => 'text-warning-800 dark:text-warning-400',
                                    default => 'text-danger-700 dark:text-danger-400',
                                };
                            @endphp
                            <li class="flex items-start gap-2 text-sm {{ $itemClasses }}">
                                @if ($finding->status === 'good')
                                    <x-heroicon-o-check-circle class="mt-0.5 h-4 w-4 shrink-0" />
                                @elseif ($finding->status === 'ok')
                                    <x-heroicon-o-exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0" />
                                @else
                                    <x-heroicon-o-x-circle class="mt-0.5 h-4 w-4 shrink-0" />
                                @endif
                                <span>{{ $finding->label() }}</span>
                            </li>
                        @empty
                            <li class="text-sm text-gray-500 dark:text-gray-400">{{ __('mksine::seo.no_findings') }}</li>
                        @endforelse
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</x-dynamic-component>
