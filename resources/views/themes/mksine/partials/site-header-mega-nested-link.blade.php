@php
    /** @var array<string, mixed> $node */
@endphp
@if (empty($node['children']))
    <a
        href="{{ $node['url'] ?: '#' }}"
        @if (! empty($node['target'])) target="{{ $node['target'] }}" @endif
        @if (($node['target'] ?? '_self') === '_blank') rel="noopener noreferrer" @endif
        class="group flex items-center gap-2 font-medium text-gray-900 hover:text-violet-600 dark:text-gray-100 dark:hover:text-violet-400"
    >
        {{ $node['label'] }}
    </a>
@else
    <div class="space-y-2 border-l border-gray-100 pl-3 dark:border-gray-700">
        <div class="text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $node['label'] }}</div>
        <div class="space-y-2">
            @foreach ($node['children'] as $sub)
                @include('mksine::themes.mksine.partials.site-header-mega-nested-link', ['node' => $sub])
            @endforeach
        </div>
    </div>
@endif
