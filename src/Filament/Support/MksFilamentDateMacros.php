<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Support;

use Closure;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Facades\FilamentAsset;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Js;
use Miran\Mksine\Support\JalaliCalendar;
use Miran\Mksine\Support\MksDateFormatter;

/**
 * The `jalali*` macros are ported from ariaieboy/filament-jalali-datetime
 * (MIT, Copyright (c) ariaieboy <smr.seddighy@gmail.com>) and mokhosh/filament-jalali
 * (MIT, Copyright (c) mokhosh <mskhoshnazar@gmail.com>).
 */
final class MksFilamentDateMacros
{
    public static function register(): void
    {
        if (! TextColumn::hasMacro('mksDate')) {
            TextColumn::macro('mksDate', function (?string $format = null, ?string $timezone = null) {
                /** @var TextColumn $column */
                $column = $this;

                $column->formatStateUsing(static function (TextColumn $column, $state) use ($format, $timezone): ?string {
                    if (blank($state)) {
                        return null;
                    }

                    $tz = $timezone ?? $column->getTimezone();

                    return MksDateFormatter::formatDate($state, $format, $tz);
                });

                return $column;
            });
        }

        if (! TextColumn::hasMacro('mksDateTime')) {
            TextColumn::macro('mksDateTime', function (?string $format = null, ?string $timezone = null) {
                /** @var TextColumn $column */
                $column = $this;

                $column->formatStateUsing(static function (TextColumn $column, $state) use ($format, $timezone): ?string {
                    if (blank($state)) {
                        return null;
                    }

                    $tz = $timezone ?? $column->getTimezone();

                    return MksDateFormatter::formatDateTime($state, $format, $tz);
                });

                return $column;
            });
        }

        if (! TextEntry::hasMacro('mksDate')) {
            TextEntry::macro('mksDate', function (?string $format = null, ?string $timezone = null) {
                /** @var TextEntry $entry */
                $entry = $this;

                $entry->formatStateUsing(static function (TextEntry $entry, $state) use ($format, $timezone): ?string {
                    if (blank($state)) {
                        return null;
                    }

                    $tz = $entry->evaluate($timezone) ?? $entry->getTimezone();

                    return MksDateFormatter::formatDate($state, $format, is_string($tz) ? $tz : null);
                });

                return $entry;
            });
        }

        if (! TextEntry::hasMacro('mksDateTime')) {
            TextEntry::macro('mksDateTime', function (?string $format = null, ?string $timezone = null) {
                /** @var TextEntry $entry */
                $entry = $this;

                $entry->formatStateUsing(static function (TextEntry $entry, $state) use ($format, $timezone): ?string {
                    if (blank($state)) {
                        return null;
                    }

                    $tz = $entry->evaluate($timezone) ?? $entry->getTimezone();

                    return MksDateFormatter::formatDateTime($state, $format, is_string($tz) ? $tz : null);
                });

                return $entry;
            });
        }

        if (! TextColumn::hasMacro('jalaliDate')) {
            TextColumn::macro('jalaliDate', function (string | Closure | null $format = null, ?string $timezone = null) {
                /** @var TextColumn $column */
                $column = $this;

                $format ??= fn (TextColumn $column): string => $column->getTable()->getDefaultDateDisplayFormat();

                $column->formatStateUsing(static function (TextColumn $column, $state) use ($format, $timezone): ?string {
                    return MksFilamentDateMacros::formatJalaliState($state, $column->evaluate($format), $timezone ?? $column->getTimezone());
                });

                return $column;
            });
        }

        if (! TextColumn::hasMacro('jalaliDateTime')) {
            TextColumn::macro('jalaliDateTime', function (string | Closure | null $format = null, ?string $timezone = null) {
                /** @var TextColumn $column */
                $column = $this;

                return $column->jalaliDate(
                    $format ?? fn (TextColumn $column): string => $column->getTable()->getDefaultDateTimeDisplayFormat(),
                    $timezone,
                );
            });
        }

        if (! TextEntry::hasMacro('jalaliDate')) {
            TextEntry::macro('jalaliDate', function (string | Closure | null $format = null, ?string $timezone = null) {
                /** @var TextEntry $entry */
                $entry = $this;

                $format ??= fn (TextEntry $entry): string => $entry->getContainer()->getDefaultDateDisplayFormat();

                $entry->formatStateUsing(static function (TextEntry $entry, $state) use ($format, $timezone): ?string {
                    return MksFilamentDateMacros::formatJalaliState($state, $entry->evaluate($format), $entry->evaluate($timezone) ?? $entry->getTimezone());
                });

                return $entry;
            });
        }

        if (! TextEntry::hasMacro('jalaliDateTime')) {
            TextEntry::macro('jalaliDateTime', function (string | Closure | null $format = null, ?string $timezone = null) {
                /** @var TextEntry $entry */
                $entry = $this;

                return $entry->jalaliDate(
                    $format ?? fn (TextEntry $entry): string => $entry->getContainer()->getDefaultDateTimeDisplayFormat(),
                    $timezone,
                );
            });
        }

        if (! DateTimePicker::hasMacro('jalali')) {
            DateTimePicker::macro('jalali', function () {
                /** @var DateTimePicker $picker */
                $picker = $this;

                return $picker
                    ->native(false)
                    ->firstDayOfWeek(6)
                    ->defaultDateDisplayFormat('Y/m/d')
                    ->defaultDateTimeDisplayFormat('Y/m/d H:i')
                    ->defaultDateTimeWithSecondsDisplayFormat('Y/m/d H:i:s')
                    ->view('mksine::filament.forms.components.jalali-date-time-picker');
            });
        }

        DateTimePicker::configureUsing(static function (DateTimePicker $picker): void {
            if (! $picker instanceof TimePicker && MksFilamentDateMacros::shouldUseJalaliPickers()) {
                $picker->jalali();
            }
        });
    }

    /**
     * Whether date pickers follow the Shamsi calendar setting. False until the settings table exists.
     */
    public static function shouldUseJalaliPickers(): bool
    {
        return once(static function (): bool {
            try {
                return MksDateFormatter::isShamsi();
            } catch (QueryException) {
                return false;
            }
        });
    }

    public static function formatJalaliState(mixed $state, string $format, ?string $timezone): ?string
    {
        if (blank($state)) {
            return null;
        }

        return JalaliCalendar::localizeDigits(
            JalaliCalendar::format(Carbon::parse($state)->setTimezone($timezone), $format),
        );
    }

    /**
     * Renders Filament's own picker markup, pointed at the Jalali Alpine component.
     */
    public static function renderJalaliDateTimePicker(DateTimePicker $picker): string
    {
        $locale = $picker->getLocale();

        $calendar = Js::from([
            'displayFormat' => $picker->getDisplayFormat(),
            'months' => JalaliCalendar::months($locale),
            'days' => JalaliCalendar::days($locale),
            'daysShort' => JalaliCalendar::shortDays($locale),
        ]);

        return strtr($picker->toEmbeddedHtml(), [
            e(FilamentAsset::getAlpineComponentSrc('date-time-picker', 'filament/forms')) => e(FilamentAsset::getAlpineComponentSrc('jalali-date-time-picker', 'miran/mksine')),
            'x-data="dateTimePickerFormComponent({' => "x-data=\"mksJalaliDateTimePicker({ calendar: {$calendar},",
        ]);
    }
}
