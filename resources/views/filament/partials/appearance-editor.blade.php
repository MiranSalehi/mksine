{{--
    Look editor shared by Settings → Appearance and the "My appearance" window.
    Host: a Livewire component using InteractsWithAppearanceEditor.
    The Alpine part applies previews to this page only (inline variables + data-* on <html>) and
    undoes them on leave, on close, or when a newer preview replaces them.
--}}
@php
    $personal = $this->isPersonalEditor();
    $look = $this->look;
    $preview = $this->previewState();
    $isRtl = __('filament-panels::layout.direction') === 'rtl';
    $defaultLabel = $personal ? __('mksine::appearance.option.company') : __('mksine::appearance.option.default');
    $parts = [
        'mode' => \Miran\Mksine\Core\Appearance\AppearanceMode::cases(),
        'rail' => \Miran\Mksine\Core\Appearance\AppearanceRail::cases(),
        'radius' => \Miran\Mksine\Core\Appearance\AppearanceRadius::cases(),
        'font' => \Miran\Mksine\Core\Appearance\AppearanceFont::cases(),
        'density' => \Miran\Mksine\Core\Appearance\AppearanceDensity::cases(),
    ];
    $railFill = [
        'dark' => 'fill-gray-800',
        'light' => 'fill-white dark:fill-gray-900',
        'brand' => null,
        '' => 'fill-white dark:fill-gray-900',
    ];
    $cardRadius = ['sharp' => 1, 'medium' => 3, 'round' => 6, '' => 3];
    $editorId = $this->getId();
@endphp

<div
    class="mksine-appearance-editor"
    x-data="{
        editor: @js($editorId),
        names: ['data-rail', 'data-radius', 'data-font', 'data-density', 'data-field', 'data-label', 'data-mode'],
        snapshot: null,
        applied: [],
        take() {
            const root = document.documentElement
            this.snapshot = {
                attrs: Object.fromEntries(this.names.map((name) => [name, root.getAttribute(name)])),
                dark: root.classList.contains('dark'),
            }
        },
        apply(state) {
            if (! this.snapshot) { this.take() }
            const root = document.documentElement
            Object.entries(state.vars).forEach(([name, value]) => {
                root.style.setProperty(name, value)
                if (! this.applied.includes(name)) { this.applied.push(name) }
            })
            this.applied.filter((name) => ! (name in state.vars)).forEach((name) => root.style.removeProperty(name))
            this.names.forEach((name) => {
                if (name in state.attrs) { root.setAttribute(name, state.attrs[name]) } else { root.removeAttribute(name) }
            })
            const dark = state.mode === 'dark' || (state.mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)
            root.classList.toggle('dark', state.mode ? dark : this.snapshot.dark)
        },
        restore() {
            if (! this.snapshot) { return }
            const root = document.documentElement
            this.applied.forEach((name) => root.style.removeProperty(name))
            this.applied = []
            Object.entries(this.snapshot.attrs).forEach(([name, value]) => {
                if (value === null) { root.removeAttribute(name) } else { root.setAttribute(name, value) }
            })
            root.classList.toggle('dark', this.snapshot.dark)
            this.snapshot = null
        },
    }"
    x-on:mks-appearance-preview.window="if ($event.detail.editor === editor) { apply($event.detail.state) }"
    x-on:mks-appearance-restore.window="if ($event.detail.editor === editor) { restore() }"
    x-on:livewire:navigating.window="restore()"
>
    <div class="@container"><div class="grid gap-6 @3xl:grid-cols-[minmax(0,1fr)_16rem] @5xl:grid-cols-[minmax(0,1fr)_19rem]">
        {{-- Settings column (its own container: cards and parts follow its width) --}}
        <div class="@container min-w-0 space-y-6">
            {{-- Ready and saved looks --}}
            <section class="space-y-3">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                    {{ __('mksine::appearance.sections.presets') }}
                </h3>
                <div class="grid grid-cols-2 gap-2.5 @sm:grid-cols-3 @2xl:grid-cols-5">
                    @foreach ($this->appearanceCards() as $card)
                        @php
                            $rail = $card['rail'] ?? '';
                            $radius = $cardRadius[$card['radius'] ?? ''];
                            $railX = $isRtl ? 92 : 0;
                            $contentX = $isRtl ? 8 : 36;
                        @endphp
                        <div wire:key="appearance-card-{{ md5($card['key']) }}" class="group relative">
                            <button
                                type="button"
                                wire:click="{{ $card['saved'] ? 'chooseSaved('.\Illuminate\Support\Js::from($card['name']).')' : "choosePreset('".$card['key']."')" }}"
                                @class([
                                    'w-full overflow-hidden rounded-xl border bg-white text-start transition dark:bg-gray-900',
                                    'border-primary-600 ring-2 ring-primary-600/30' => $card['active'],
                                    'border-gray-200 hover:border-gray-300 dark:border-white/10 dark:hover:border-white/20' => ! $card['active'],
                                ])
                                aria-pressed="{{ $card['active'] ? 'true' : 'false' }}"
                            >
                                <svg viewBox="0 0 120 72" class="block h-auto w-full" aria-hidden="true">
                                    <rect width="120" height="72" class="fill-gray-50 dark:fill-gray-950" />
                                    <rect
                                        x="{{ $railX }}" width="28" height="72"
                                        @if ($rail === 'brand') style="fill: {{ $card['brand'] }}" @else class="{{ $railFill[$rail] }}" @endif
                                    />
                                    @if ($rail !== 'dark' && $rail !== 'brand')
                                        <line x1="{{ $isRtl ? 92 : 28 }}" x2="{{ $isRtl ? 92 : 28 }}" y1="0" y2="72" class="stroke-gray-200 dark:stroke-white/10" />
                                    @endif
                                    @foreach ([12, 22, 32] as $index => $y)
                                        <rect x="{{ $isRtl ? 96 : 4 }}" y="{{ $y }}" width="20" height="3" rx="1.5" @class(['fill-gray-500' => $rail === 'dark', 'fill-white/70' => $rail === 'brand', 'fill-gray-300 dark:fill-gray-600' => $rail !== 'dark' && $rail !== 'brand']) />
                                        <rect x="{{ $contentX }}" y="{{ $y }}" width="{{ 76 - $index * 14 }}" height="4" rx="{{ min($radius, 2) }}" class="fill-gray-200 dark:fill-gray-700" />
                                    @endforeach
                                    <rect x="{{ $isRtl ? 52 : 36 }}" y="48" width="32" height="12" rx="{{ $radius }}" style="fill: {{ $card['primary'] }}" />
                                </svg>
                                <span class="block truncate px-2.5 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200">
                                    {{ $card['label'] }}
                                </span>
                            </button>
                            @if ($card['saved'] && ! $personal)
                                <button
                                    type="button"
                                    wire:click="deleteNamedAppearance({{ \Illuminate\Support\Js::from($card['name']) }})"
                                    wire:confirm="{{ __('mksine::appearance.saved_looks.delete_confirm', ['name' => $card['name']]) }}"
                                    class="absolute end-1.5 top-1.5 hidden rounded-full bg-white/90 p-0.5 text-gray-500 shadow-sm hover:text-danger-600 group-hover:block focus:block dark:bg-gray-800/90"
                                    title="{{ __('mksine::appearance.saved_looks.delete') }}"
                                >
                                    <x-filament::icon icon="heroicon-m-x-mark" class="size-4" />
                                    <span class="sr-only">{{ __('mksine::appearance.saved_looks.delete') }}</span>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Primary colour --}}
            <section class="space-y-3">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                    {{ __('mksine::appearance.parts.primary') }}
                </h3>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        wire:click="setPrimary"
                        @class([
                            'flex h-8 items-center gap-1.5 rounded-full border px-2.5 text-xs font-medium',
                            'border-primary-600 text-primary-700 ring-2 ring-primary-600/30 dark:text-primary-300' => ($look['primary'] ?? null) === null,
                            'border-gray-300 text-gray-600 hover:border-gray-400 dark:border-white/15 dark:text-gray-300' => ($look['primary'] ?? null) !== null,
                        ])
                        title="{{ $defaultLabel }}"
                    >
                        <span class="size-4 rounded-full" style="background-color: {{ $personal ? ($this->appearanceBase()->primary ?? $this->defaultPrimaryHex()) : $this->defaultPrimaryHex() }}"></span>
                        {{ $defaultLabel }}
                    </button>
                    @foreach ($this->readyColors() as $name => $hex)
                        <button
                            type="button"
                            wire:key="appearance-color-{{ $name }}"
                            wire:click="setPrimary('{{ $hex }}')"
                            @class([
                                'size-8 rounded-full ring-offset-2 ring-offset-white transition dark:ring-offset-gray-900',
                                'ring-2 ring-gray-950 dark:ring-white' => ($look['primary'] ?? null) === $hex,
                                'hover:scale-110' => ($look['primary'] ?? null) !== $hex,
                            ])
                            style="background-color: {{ $hex }}"
                            title="{{ __('mksine::appearance.colors.'.$name) }}"
                            aria-pressed="{{ ($look['primary'] ?? null) === $hex ? 'true' : 'false' }}"
                        >
                            <span class="sr-only">{{ __('mksine::appearance.colors.'.$name) }}</span>
                        </button>
                    @endforeach
                    <label class="relative size-8 cursor-pointer overflow-hidden rounded-full border border-dashed border-gray-400 dark:border-white/30" title="{{ __('mksine::appearance.color_picker') }}">
                        <input
                            type="color"
                            class="absolute inset-0 size-full cursor-pointer opacity-0"
                            value="{{ $look['primary'] ?? $this->defaultPrimaryHex() }}"
                            x-on:input.debounce.150ms="$wire.setPrimary($event.target.value)"
                        />
                        <x-filament::icon icon="heroicon-m-eye-dropper" class="pointer-events-none absolute inset-0 m-auto size-4 text-gray-500" />
                        <span class="sr-only">{{ __('mksine::appearance.color_picker') }}</span>
                    </label>
                    <div class="w-32">
                        <x-filament::input.wrapper :valid="! $this->primaryInputInvalid">
                            <x-filament::input
                                type="text"
                                dir="ltr"
                                maxlength="7"
                                placeholder="#4f46e5"
                                wire:model.live.debounce.500ms="primaryInput"
                                aria-label="{{ __('mksine::appearance.hex') }}"
                            />
                        </x-filament::input.wrapper>
                    </div>
                </div>
                @if ($this->primaryInputInvalid)
                    <p class="text-sm text-danger-600 dark:text-danger-400">{{ __('mksine::appearance.errors.bad_hex') }}</p>
                @endif
                @if ($preview['adjusted'])
                    <p class="flex items-center gap-2 rounded-lg bg-info-50 px-3 py-2 text-sm text-info-700 dark:bg-info-400/10 dark:text-info-300">
                        <x-filament::icon icon="heroicon-m-information-circle" class="size-5 shrink-0" />
                        <span>{{ __('mksine::appearance.notices.adjusted') }}</span>
                        <span class="ms-auto inline-flex items-center gap-1.5 font-mono text-xs" dir="ltr">
                            <span class="size-3 rounded-full" style="background-color: {{ $preview['used'] }}"></span>{{ $preview['used'] }}
                        </span>
                    </p>
                @endif
                @if ($preview['nearDanger'])
                    <p class="flex items-center gap-2 rounded-lg bg-warning-50 px-3 py-2 text-sm text-warning-800 dark:bg-warning-400/10 dark:text-warning-300">
                        <x-filament::icon icon="heroicon-m-exclamation-triangle" class="size-5 shrink-0" />
                        <span>{{ __('mksine::appearance.notices.near_danger') }}</span>
                    </p>
                @endif
            </section>

            {{-- Segmented parts --}}
            <section class="grid gap-x-6 gap-y-4 @2xl:grid-cols-2">
                @foreach ($parts as $part => $cases)
                    <div class="space-y-1.5" wire:key="appearance-part-{{ $part }}">
                        <span class="block text-sm font-medium text-gray-950 dark:text-white" id="appearance-{{ $editorId }}-{{ $part }}">
                            {{ __('mksine::appearance.parts.'.$part) }}
                        </span>
                        @include('mksine::filament.partials.appearance-segmented', [
                            'part' => $part,
                            'labelledBy' => 'appearance-'.$editorId.'-'.$part,
                            'current' => $look[$part] ?? null,
                            'options' => array_merge(
                                ['' => $defaultLabel],
                                collect($cases)->mapWithKeys(fn ($case): array => [$case->value => __('mksine::appearance.values.'.$part.'.'.$case->value)])->all(),
                            ),
                        ])
                    </div>
                @endforeach
            </section>

            @includeIf('mksine::filament.partials.appearance-editor-forms')

            {{-- Footer --}}
            <div class="flex flex-wrap items-center gap-3 border-t border-gray-200 pt-4 dark:border-white/10">
                @if ($this->hasUnsavedAppearance())
                    <x-filament::badge color="warning" icon="heroicon-m-pencil-square">
                        {{ __('mksine::appearance.unsaved') }}
                    </x-filament::badge>
                @endif

                <x-filament::button wire:click="saveAppearance" wire:loading.attr="disabled">
                    {{ $personal ? __('mksine::appearance.my.save') : __('mksine::appearance.company.save') }}
                </x-filament::button>

                @if (! $personal)
                    <x-filament::button color="gray" x-on:click="$dispatch('open-modal', { id: 'mks-appearance-save-named' })">
                        {{ __('mksine::appearance.saved_looks.save_as') }}
                    </x-filament::button>
                @endif

                <x-filament::button color="gray" wire:click="revertAppearance" :disabled="! $this->hasUnsavedAppearance()">
                    {{ __('mksine::appearance.revert') }}
                </x-filament::button>

                @if ($personal)
                    <x-filament::link tag="button" color="gray" wire:click="useCompanyLook" class="ms-auto">
                        {{ __('mksine::appearance.my.use_company') }}
                    </x-filament::link>
                @else
                    <x-filament::link tag="button" color="gray" wire:click="resetToAppDefault" class="ms-auto">
                        {{ __('mksine::appearance.company.reset_default') }}
                    </x-filament::link>
                @endif
            </div>
        </div>

        {{-- Live preview --}}
        <aside class="@3xl:sticky @3xl:top-20 @3xl:self-start">
            <div class="space-y-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('mksine::appearance.preview.title') }}</h3>

                <div class="flex flex-wrap gap-2">
                    <x-filament::button size="sm">{{ __('mksine::appearance.preview.save') }}</x-filament::button>
                    <x-filament::button size="sm" color="gray">{{ __('mksine::appearance.preview.cancel') }}</x-filament::button>
                    <x-filament::button size="sm" color="danger" outlined>{{ __('mksine::appearance.preview.delete') }}</x-filament::button>
                </div>

                <div class="flex flex-wrap gap-1.5">
                    <x-filament::badge>{{ __('mksine::appearance.preview.badge_new') }}</x-filament::badge>
                    <x-filament::badge color="success">{{ __('mksine::appearance.preview.badge_paid') }}</x-filament::badge>
                    <x-filament::badge color="warning">{{ __('mksine::appearance.preview.badge_pending') }}</x-filament::badge>
                    <x-filament::badge color="danger">{{ __('mksine::appearance.preview.badge_overdue') }}</x-filament::badge>
                </div>

                <x-filament::input.wrapper>
                    <x-filament::input type="text" :placeholder="__('mksine::appearance.preview.field')" />
                </x-filament::input.wrapper>

                <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
                    <table class="w-full text-start text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 dark:bg-white/5 dark:text-gray-400">
                            <tr>
                                <th class="px-3 py-2 text-start font-medium">{{ __('mksine::appearance.preview.col_name') }}</th>
                                <th class="px-3 py-2 text-end font-medium">{{ __('mksine::appearance.preview.col_amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                            @foreach ([['row_1', '1,200'], ['row_2', '860'], ['row_3', '2,450']] as [$row, $amount])
                                <tr class="text-gray-700 dark:text-gray-200">
                                    <td class="px-3 py-2">{{ __('mksine::appearance.preview.'.$row) }}</td>
                                    <td class="px-3 py-2 text-end tabular-nums">{{ $amount }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-filament::link tag="button" type="button" icon="heroicon-m-arrow-top-right-on-square">
                    {{ __('mksine::appearance.preview.link') }}
                </x-filament::link>
            </div>
        </aside>
    </div></div>
</div>
