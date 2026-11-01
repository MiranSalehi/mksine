<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use Carbon\CarbonInterface;

/**
 * Dependency-free Jalali (Shamsi) calendar arithmetic and PHP-style date formatting.
 *
 * Uses the 33-year leap cycle of ICU's Persian calendar, so results match {@see MksDateFormatter}.
 * `resources/js/jalali-date-time-picker.js` implements the same arithmetic for the browser.
 */
final class JalaliCalendar
{
    /** Julian day number of 1 Farvardin, year 1. */
    private const int EPOCH_JULIAN_DAY = 1948320;

    private const int UNIX_EPOCH_JULIAN_DAY = 2440588;

    /**
     * @return array{year: int, month: int, day: int}
     */
    public static function fromGregorian(int $year, int $month, int $day): array
    {
        $unixDay = (int) floor(gmmktime(0, 0, 0, $month, $day, $year) / 86400);
        $daysSinceEpoch = $unixDay + self::UNIX_EPOCH_JULIAN_DAY - self::EPOCH_JULIAN_DAY;

        $jalaliYear = 1 + (int) floor((33 * $daysSinceEpoch + 3) / 12053);
        $dayOfYear = $daysSinceEpoch - self::daysBeforeYear($jalaliYear);
        $jalaliMonth = 1 + ($dayOfYear < 186 ? intdiv($dayOfYear, 31) : intdiv($dayOfYear - 6, 30));

        return [
            'year' => $jalaliYear,
            'month' => $jalaliMonth,
            'day' => $dayOfYear - self::daysBeforeMonth($jalaliMonth) + 1,
        ];
    }

    public static function isLeapYear(int $year): bool
    {
        return ((((25 * $year + 11) % 33) + 33) % 33) < 8;
    }

    public static function daysInMonth(int $year, int $month): int
    {
        return match (true) {
            $month <= 6 => 31,
            $month <= 11 => 30,
            default => self::isLeapYear($year) ? 30 : 29,
        };
    }

    /**
     * Formats with PHP date characters. `Y y m n d j t L F M l D` follow the Jalali calendar;
     * every other character is passed to Carbon. Prefix a character with `\` to print it as is.
     */
    public static function format(CarbonInterface $date, string $format, ?string $locale = null): string
    {
        ['year' => $year, 'month' => $month, 'day' => $day] = self::fromGregorian($date->year, $date->month, $date->day);

        $formatted = '';
        $isEscaped = false;

        foreach (mb_str_split($format) as $character) {
            if ($isEscaped) {
                $formatted .= $character;
                $isEscaped = false;

                continue;
            }

            if ($character === '\\') {
                $isEscaped = true;

                continue;
            }

            $formatted .= match ($character) {
                'Y' => (string) $year,
                'y' => str_pad((string) ($year % 100), 2, '0', STR_PAD_LEFT),
                'm' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
                'n' => (string) $month,
                'd' => str_pad((string) $day, 2, '0', STR_PAD_LEFT),
                'j' => (string) $day,
                't' => (string) self::daysInMonth($year, $month),
                'L' => self::isLeapYear($year) ? '1' : '0',
                'F' => self::months($locale)[$month - 1],
                'M' => mb_substr(self::months($locale)[$month - 1], 0, 3),
                'l' => self::days($locale)[$date->dayOfWeek],
                'D' => self::shortDays($locale)[$date->dayOfWeek],
                default => $date->format($character),
            };
        }

        return $formatted;
    }

    /**
     * Converts Latin digits to Persian digits when the locale is `fa`.
     */
    public static function localizeDigits(string $value, ?string $locale = null): string
    {
        if (($locale ?? app()->getLocale()) !== 'fa') {
            return $value;
        }

        return strtr($value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }

    /**
     * @return list<string> Farvardin first.
     */
    public static function months(?string $locale = null): array
    {
        return trans('mksine::jalali.months', [], $locale);
    }

    /**
     * @return list<string> Sunday first.
     */
    public static function days(?string $locale = null): array
    {
        return trans('mksine::jalali.days', [], $locale);
    }

    /**
     * @return list<string> Sunday first.
     */
    public static function shortDays(?string $locale = null): array
    {
        return trans('mksine::jalali.days_short', [], $locale);
    }

    private static function daysBeforeYear(int $year): int
    {
        return 365 * ($year - 1) + (int) floor((8 * $year + 21) / 33);
    }

    private static function daysBeforeMonth(int $month): int
    {
        return $month <= 7 ? 31 * ($month - 1) : 30 * ($month - 1) + 6;
    }
}
