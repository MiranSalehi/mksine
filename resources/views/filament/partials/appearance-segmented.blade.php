{{--
    Segmented control for one look part.
    @var string $part, string $labelledBy, string|null $current, array<string, string> $options ('' = empty/default)
--}}
<div
    role="radiogroup"
    aria-labelledby="{{ $labelledBy }}"
    class="inline-flex max-w-full flex-wrap gap-0.5 rounded-lg bg-gray-100 p-0.5 dark:bg-white/5"
>
    @foreach ($options as $value => $label)
        @php
            $value = (string) $value;
            $isActive = ($current ?? '') === $value;
        @endphp
        <button
            type="button"
            role="radio"
            aria-checked="{{ $isActive ? 'true' : 'false' }}"
            wire:click="setPart('{{ $part }}', {{ $value === '' ? 'null' : "'".$value."'" }})"
            @class([
                'rounded-md px-3 py-1.5 text-sm font-medium transition',
                'bg-white text-gray-950 shadow-sm dark:bg-white/10 dark:text-white' => $isActive,
                'text-gray-600 hover:text-gray-950 dark:text-gray-400 dark:hover:text-white' => ! $isActive,
            ])
        >
            {{ $label }}
        </button>
    @endforeach
</div>
