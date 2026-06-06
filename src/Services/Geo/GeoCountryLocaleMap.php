<?php

declare(strict_types=1);

namespace Miran\Mksine\Services\Geo;

/**
 * Maps ISO2 country codes to the primary translation locale used for native name fixes.
 */
final class GeoCountryLocaleMap
{
    /**
     * @var array<string, string>
     */
    private const array MAP = [
        'IR' => 'fa',
        'SA' => 'ar',
        'AE' => 'ar',
        'EG' => 'ar',
        'IQ' => 'ar',
        'JO' => 'ar',
        'KW' => 'ar',
        'LB' => 'ar',
        'OM' => 'ar',
        'QA' => 'ar',
        'SY' => 'ar',
        'YE' => 'ar',
        'TR' => 'tr',
        'DE' => 'de',
        'AT' => 'de',
        'CH' => 'de',
        'JP' => 'ja',
        'CN' => 'zh-CN',
        'TW' => 'zh-TW',
        'KR' => 'ko',
        'FR' => 'fr',
        'ES' => 'es',
        'IT' => 'it',
        'RU' => 'ru',
        'UA' => 'uk',
        'PL' => 'pl',
        'NL' => 'nl',
        'PT' => 'pt',
        'BR' => 'pt-BR',
        'IN' => 'hi',
        'KU' => 'ku',
    ];

    public static function localeForCountry(string $iso2): ?string
    {
        $code = strtoupper(trim($iso2));

        return self::MAP[$code] ?? null;
    }

    /**
     * @return list<string>
     */
    public static function mappedCountryCodes(): array
    {
        return array_keys(self::MAP);
    }

    /**
     * @param  array<string, mixed>|null  $translations
     */
    public static function resolveNativeName(
        string $iso2,
        ?string $native,
        ?string $name,
        ?array $translations = null,
    ): ?string {
        $locale = self::localeForCountry($iso2);

        if ($locale !== null && is_array($translations)) {
            $fromTranslation = $translations[$locale] ?? null;
            if (is_string($fromTranslation) && trim($fromTranslation) !== '') {
                return trim($fromTranslation);
            }
        }

        if (is_string($native) && trim($native) !== '') {
            return trim($native);
        }

        return is_string($name) && trim($name) !== '' ? trim($name) : null;
    }
}
