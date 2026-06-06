<?php

declare(strict_types=1);

namespace Miran\Mksine\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use IntlDateFormatter;

/**
 * Formats dates for admin and storefront display based on the {@see self::SETTING_KEY} setting.
 *
 * Storage remains Gregorian; only presentation switches between Gregorian and Shamsi (Jalali).
 */
final class MksDateFormatter
{
    public const string SETTING_KEY = 'date_calendar';

    public const string GREGORIAN = 'gregorian';

    public const string SHAMSI = 'shamsi';

    /** @deprecated Use {@see self::SETTING_KEY} via core settings. */
    public const string LEGACY_BOOKING_SETTING_KEY = 'mks_booking_date_type';

    public const string DEFAULT_DATE_FORMAT_GREGORIAN = 'Y-m-d';

    public const string DEFAULT_DATETIME_FORMAT_GREGORIAN = 'Y-m-d H:i';

    public const string DEFAULT_DATE_FORMAT_SHAMSI = 'yyyy/MM/dd';

    public const string DEFAULT_DATETIME_FORMAT_SHAMSI = 'yyyy/MM/dd HH:mm';

    public static function calendar(): string
    {
        $value = mks_setting(self::SETTING_KEY);

        if (is_string($value) && in_array($value, [self::GREGORIAN, self::SHAMSI], true)) {
            return $value;
        }

        $legacy = mks_setting(self::LEGACY_BOOKING_SETTING_KEY);

        if (is_string($legacy) && in_array($legacy, [self::GREGORIAN, self::SHAMSI], true)) {
            return $legacy;
        }

        return self::GREGORIAN;
    }

    public static function isShamsi(): bool
    {
        return self::calendar() === self::SHAMSI;
    }

    public static function formatDate(
        DateTimeInterface|string|null $date,
        ?string $format = null,
        ?string $timezone = null,
    ): ?string {
        return self::format(
            $date,
            $format ?? (self::isShamsi() ? self::DEFAULT_DATE_FORMAT_SHAMSI : self::DEFAULT_DATE_FORMAT_GREGORIAN),
            $timezone,
        );
    }

    public static function formatDateTime(
        DateTimeInterface|string|null $date,
        ?string $format = null,
        ?string $timezone = null,
    ): ?string {
        return self::format(
            $date,
            $format ?? (self::isShamsi() ? self::DEFAULT_DATETIME_FORMAT_SHAMSI : self::DEFAULT_DATETIME_FORMAT_GREGORIAN),
            $timezone,
        );
    }

    public static function format(
        DateTimeInterface|string|null $date,
        ?string $format = null,
        ?string $timezone = null,
    ): ?string {
        if ($date === null || $date === '') {
            return null;
        }

        $carbon = Carbon::parse($date);

        if ($timezone !== null && $timezone !== '') {
            $carbon = $carbon->timezone($timezone);
        }

        $format ??= self::isShamsi()
            ? self::DEFAULT_DATETIME_FORMAT_SHAMSI
            : self::DEFAULT_DATETIME_FORMAT_GREGORIAN;

        if (! self::isShamsi()) {
            return $carbon->format($format);
        }

        return self::formatShamsi($carbon, $format);
    }

    private static function formatShamsi(CarbonInterface $date, string $pattern): string
    {
        if (! extension_loaded('intl') || ! class_exists(IntlDateFormatter::class)) {
            return $date->format(self::shamsiPatternToPhp($pattern));
        }

        $locale = self::persianIntlLocale();

        $formatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            $date->getTimezone()->getName(),
            IntlDateFormatter::TRADITIONAL,
            $pattern,
        );

        $formatted = $formatter->format($date);

        return $formatted !== false ? $formatted : $date->format(self::shamsiPatternToPhp($pattern));
    }

    private static function persianIntlLocale(): string
    {
        $appLocale = (string) app()->getLocale();

        return match ($appLocale) {
            'ku' => 'ckb_IR@calendar=persian',
            'fa' => 'fa_IR@calendar=persian',
            default => 'fa_IR@calendar=persian',
        };
    }

    /**
     * Fallback when intl is unavailable: approximate Gregorian formatting.
     */
    private static function shamsiPatternToPhp(string $pattern): string
    {
        return str_replace(
            ['yyyy', 'MM', 'dd', 'HH', 'mm', 'ss'],
            ['Y', 'm', 'd', 'H', 'i', 's'],
            $pattern,
        );
    }
}
