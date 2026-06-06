<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Support;

use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Miran\Mksine\Support\MksDateFormatter;

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
    }
}
