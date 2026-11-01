@php
    use Filament\Actions\Action;
    use Filament\Support\Enums\VerticalAlignment;
    use Filament\Support\Facades\FilamentAsset;
    use Illuminate\Support\Js;
    use Miran\Mksine\Support\JalaliCalendar;

    $disabledDates = $field->getDisabledDates();
    $hasDate = $field->hasDate();
    $hasTime = $field->hasTime();
    $hasSeconds = $field->hasSeconds();
    $id = $field->getId();
    $isDisabled = $field->isDisabled();
    $isReadOnly = $field->isReadOnly();
    $isInteractive = ! ($isDisabled || $isReadOnly);
    $locale = $field->getLocale();
    $maxDate = $field->getMaxDate();
    $minDate = $field->getMinDate();
    $placeholder = $field->getPlaceholder();
    $statePath = $field->getStatePath();
    $livewireKey = $field->getLivewireKey();

    $prefixLabel = $field->getPrefixLabel();
    $prefixActions = array_filter($field->getPrefixActions(), static fn (Action $action): bool => $action->isVisible());
    $prefixIcon = $field->getPrefixIcon();
    $suffixLabel = $field->getSuffixLabel();
    $suffixActions = array_filter($field->getSuffixActions(), static fn (Action $action): bool => $action->isVisible());
    $suffixIcon = $field->getSuffixIcon();

    $calendar = [
        'displayFormat' => $field->getDisplayFormat(),
        'months' => JalaliCalendar::months($locale),
        'days' => JalaliCalendar::days($locale),
        'daysShort' => JalaliCalendar::shortDays($locale),
    ];
@endphp

<x-dynamic-component
    :component="$field->getFieldWrapperView()"
    :field="$field"
    :inline-label-vertical-alignment="VerticalAlignment::Center"
>
    <x-filament::input.wrapper
        :disabled="$isDisabled"
        :inline-prefix="$field->isPrefixInline()"
        :inline-suffix="$field->isSuffixInline()"
        :prefix="$prefixLabel"
        :prefix-actions="$prefixActions"
        :prefix-icon="$prefixIcon"
        :prefix-icon-color="$field->getPrefixIconColor() ?? 'gray'"
        :suffix="$suffixLabel"
        :suffix-actions="$suffixActions"
        :suffix-icon="$suffixIcon"
        :suffix-icon-color="$field->getSuffixIconColor() ?? 'gray'"
        :valid="! $field->getLivewire()->getErrorBag()->has($statePath)"
        :attributes="$field->getExtraAttributeBag()->class(['mks-jalali-picker'])"
    >
        <div
            x-load
            x-load-src="{{ FilamentAsset::getAlpineComponentSrc('jalali-date-time-picker', 'miran/mksine') }}"
            x-data="mksJalaliDateTimePicker({
                        calendar: {{ Js::from($calendar) }},
                        defaultFocusedDate: {{ Js::from($field->getDefaultFocusedDate()) }},
                        firstDayOfWeek: {{ $field->getFirstDayOfWeek() }},
                        isAutofocused: {{ Js::from($field->isAutofocused()) }},
                        isInteractive: {{ Js::from($isInteractive) }},
                        shouldCloseOnDateSelection: {{ Js::from($field->shouldCloseOnDateSelection()) }},
                        state: $wire.{!! $field->applyStateBindingModifiers("\$entangle('{$statePath}')") !!},
                    })"
            wire:ignore
            wire:key="{{ $livewireKey }}.{{ substr(md5(serialize([$disabledDates, $isDisabled, $isReadOnly, $maxDate, $minDate, $hasDate, $hasTime, $hasSeconds])), 0, 64) }}"
            x-on:click.outside="closePanel()"
            x-on:dropdown-escape="if (isOpen()) { closePanel(true); $event.preventDefault() }"
            x-on:focusout="if (isOpen() && $event.relatedTarget && ! $el.contains($event.relatedTarget)) closePanel()"
            x-on:keydown.escape="if (isOpen()) { $event.preventDefault(); $event.stopPropagation(); closePanel(true) }"
            x-on:livewire:navigating.window="closePanel()"
            {{ $field->getExtraAlpineAttributeBag() }}
        >
            <input x-ref="maxDate" type="hidden" value="{{ $maxDate }}" />
            <input x-ref="minDate" type="hidden" value="{{ $minDate }}" />
            <input x-ref="disabledDates" type="hidden" value="{{ json_encode($disabledDates) }}" />

            <input
                x-ref="button"
                x-model="displayText"
                x-on:click="togglePanelVisibility()"
                x-on:keydown.enter.prevent.stop="isOpen() ? selectDate() : togglePanelVisibility()"
                x-on:keydown.arrow-left.prevent.stop="focusPreviousDay()"
                x-on:keydown.arrow-right.prevent.stop="focusNextDay()"
                x-on:keydown.arrow-up.prevent.stop="focusPreviousWeek()"
                x-on:keydown.arrow-down.prevent.stop="focusNextWeek()"
                x-on:keydown.backspace.prevent.stop="clearState()"
                x-on:keydown.delete.prevent.stop="clearState()"
                x-bind:aria-expanded="isPanelOpen.toString()"
                aria-haspopup="dialog"
                @if ($field->isAutofocused()) autofocus @endif
                @disabled($isDisabled)
                @if (filled($id)) id="{{ $id }}" @endif
                placeholder="{{ $placeholder }}"
                readonly
                role="combobox"
                type="text"
                wire:key="{{ $livewireKey }}.display-text"
                {{ $field->getExtraInputAttributeBag()->class(['mks-jalali-picker-input']) }}
            />

            <div
                x-ref="panel"
                x-bind:class="{ 'mks-jalali-picker-panel-open': isPanelOpen }"
                role="dialog"
                wire:key="{{ $livewireKey }}.panel"
                class="mks-jalali-picker-panel"
            >
                @if ($hasDate)
                    <div class="mks-jalali-picker-header">
                        <select x-model="focusedMonth" class="mks-jalali-picker-month">
                            <template x-for="(month, index) in months" x-bind:key="index">
                                <option x-bind:value="index" x-text="month"></option>
                            </template>
                        </select>

                        <input type="number" inputmode="numeric" x-model.debounce="focusedYear" class="mks-jalali-picker-year" />
                    </div>

                    <div class="mks-jalali-picker-weekdays">
                        <template x-for="(day, index) in dayLabels" x-bind:key="index">
                            <div x-text="day"></div>
                        </template>
                    </div>

                    <div role="grid" class="mks-jalali-picker-days">
                        <template x-for="day in emptyDaysInFocusedMonth" x-bind:key="`empty-${day}`">
                            <div></div>
                        </template>

                        <template x-for="day in daysInFocusedMonth" x-bind:key="day">
                            <button
                                x-text="day"
                                x-on:click="dayIsDisabled(day) || selectDate(day)"
                                x-on:mouseenter="setFocusedDay(day)"
                                x-bind:aria-selected="dayIsSelected(day)"
                                x-bind:class="{
                                    'mks-jalali-picker-today': dayIsToday(day),
                                    'mks-jalali-picker-focused': focusedDate?.date() === day,
                                    'mks-jalali-picker-selected': dayIsSelected(day),
                                }"
                                x-bind:disabled="dayIsDisabled(day)"
                                role="gridcell"
                                tabindex="-1"
                                type="button"
                            ></button>
                        </template>
                    </div>
                @endif

                @if ($hasTime)
                    <div dir="ltr" class="mks-jalali-picker-time">
                        <input max="23" min="0" step="{{ $field->getHoursStep() }}" type="number" inputmode="numeric" x-on:blur="checkTimeInputValidity" x-on:invalid="timeInputInvalid" x-model.debounce="hour" />
                        <span>:</span>
                        <input max="59" min="0" step="{{ $field->getMinutesStep() }}" type="number" inputmode="numeric" x-on:blur="checkTimeInputValidity" x-on:invalid="timeInputInvalid" x-model.debounce="minute" />

                        @if ($hasSeconds)
                            <span>:</span>
                            <input max="59" min="0" step="{{ $field->getSecondsStep() }}" type="number" inputmode="numeric" x-on:blur="checkTimeInputValidity" x-on:invalid="timeInputInvalid" x-model.debounce="second" />
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </x-filament::input.wrapper>
</x-dynamic-component>
