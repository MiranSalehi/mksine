{{--
    "Forms" section of the look editor: field shape (with a small drawing of each) and label position.
    Included by appearance-editor.blade.php; same host component.
--}}
@php
    $currentField = $look['field'] ?? null;
    $effectiveField = $this->effectiveAppearance()->field?->value;
    $fieldShapes = \Miran\Mksine\Core\Appearance\AppearanceField::cases();
@endphp

<section class="space-y-4" wire:key="appearance-forms">
    <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
        {{ __('mksine::appearance.sections.forms') }}
    </h3>

    <div class="space-y-1.5">
        <span class="block text-sm font-medium text-gray-950 dark:text-white">{{ __('mksine::appearance.parts.field') }}</span>
        <div class="grid grid-cols-2 gap-2.5 @md:grid-cols-5">
            @foreach (array_merge([null], $fieldShapes) as $shape)
                @php
                    $value = $shape?->value;
                    $isActive = $currentField === $value;
                    $drawn = $value ?? ($personal ? ($this->appearanceBase()->field?->value ?? 'filled') : 'filled');
                @endphp
                <button
                    type="button"
                    wire:key="appearance-field-{{ $value ?? 'default' }}"
                    wire:click="setPart('field', {{ $value === null ? 'null' : "'".$value."'" }})"
                    aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                    @class([
                        'overflow-hidden rounded-xl border bg-white text-start transition dark:bg-gray-900',
                        'border-primary-600 ring-2 ring-primary-600/30' => $isActive,
                        'border-gray-200 hover:border-gray-300 dark:border-white/10 dark:hover:border-white/20' => ! $isActive,
                    ])
                >
                    <svg viewBox="0 0 80 44" class="block h-auto w-full" aria-hidden="true">
                        <rect width="80" height="44" class="fill-gray-50 dark:fill-gray-950" />
                        @if ($drawn === 'floating')
                            <rect x="10" y="14" width="60" height="18" rx="3" class="fill-white stroke-gray-400 dark:fill-gray-900 dark:stroke-gray-600" stroke-width="1" />
                            <rect x="{{ $isRtl ? 46 : 14 }}" y="11" width="20" height="6" class="fill-gray-50 dark:fill-gray-950" />
                            <rect x="{{ $isRtl ? 48 : 16 }}" y="12.5" width="16" height="3" rx="1.5" class="fill-primary-600" />
                        @else
                            <rect x="{{ $isRtl ? 50 : 10 }}" y="7" width="20" height="3" rx="1.5" class="fill-gray-400 dark:fill-gray-500" />
                            @if ($drawn === 'underline')
                                <line x1="10" x2="70" y1="31" y2="31" class="stroke-gray-400 dark:stroke-gray-500" stroke-width="1.5" />
                            @elseif ($drawn === 'outlined')
                                <rect x="10" y="14" width="60" height="18" rx="3" class="fill-white stroke-gray-400 dark:fill-gray-900 dark:stroke-gray-500" stroke-width="1.25" />
                            @else
                                <rect x="10" y="14" width="60" height="18" rx="3" class="fill-white stroke-gray-200 dark:fill-white/5 dark:stroke-white/20" stroke-width="1" />
                            @endif
                        @endif
                        <rect x="{{ $isRtl ? 44 : 14 }}" y="21.5" width="22" height="3" rx="1.5" class="fill-gray-300 dark:fill-gray-600" />
                    </svg>
                    <span class="block truncate px-2.5 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200">
                        {{ $value === null ? $defaultLabel : __('mksine::appearance.values.field.'.$value) }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    <div class="space-y-1.5">
        <span class="block text-sm font-medium text-gray-950 dark:text-white" id="appearance-{{ $editorId }}-label">
            {{ __('mksine::appearance.parts.label') }}
        </span>
        @include('mksine::filament.partials.appearance-segmented', [
            'part' => 'label',
            'labelledBy' => 'appearance-'.$editorId.'-label',
            'current' => $look['label'] ?? null,
            'options' => array_merge(
                ['' => $defaultLabel],
                collect(\Miran\Mksine\Core\Appearance\AppearanceLabel::cases())->mapWithKeys(fn ($case): array => [$case->value => __('mksine::appearance.values.label.'.$case->value)])->all(),
            ),
        ])
        @if ($effectiveField === 'floating')
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('mksine::appearance.notices.label_with_floating') }}</p>
        @else
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('mksine::appearance.notices.label_side_narrow') }}</p>
        @endif
    </div>
</section>
