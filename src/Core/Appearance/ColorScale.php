<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Appearance;

use Filament\Support\Colors\Color;

/**
 * Builds the admin palettes from one primary colour, in OKLCH.
 *
 * Filament's own {@see Color::generatePalette()} keeps only the hue (fixed lightness and chroma per
 * step), so step 600 would not be the chosen colour. Here step 600 IS the chosen colour, darkened
 * only as far as white text on it needs to reach 4.5:1, and never below lightness 0.3 so the darker
 * steps keep room. Colours outside sRGB lose chroma (never lightness or hue) until they fit.
 *
 * Same formulas as the accounting app's `lookCore.ts`, plus step 950.
 */
final class ColorScale
{
    /** WCAG AA for normal text: white text on step 600. */
    public const float MIN_CONTRAST = 4.5;

    /** Step 600 never goes darker than this OKLCH lightness. */
    public const float LIGHTNESS_FLOOR = 0.3;

    /** A primary whose hue is this close to the danger red gets a warning. */
    public const float NEAR_DANGER_DEGREES = 14.0;

    /** Below this chroma a colour counts as grey. */
    public const float ACHROMATIC_CHROMA = 0.03;

    /** Most chroma the greys borrow from the primary hue. */
    public const float GRAY_TINT_CHROMA = 0.015;

    /** The accent ("second colour") hue sits this many degrees below the primary hue. */
    public const float ACCENT_HUE_SHIFT = 40.0;

    /** @var list<int> */
    public const array STEPS = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950];

    /** Chroma of each step, as a share of the primary's chroma. */
    private const array CHROMA = [0.12, 0.22, 0.42, 0.65, 0.88, 0.98, 1.0, 0.92, 0.8, 0.66, 0.5];

    private const float DARKEN_STEP = 0.005;

    /**
     * `#rgb` / `#rrggbb` (any case) to lowercase `#rrggbb`; anything else is null.
     */
    public static function normalizeHex(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = strtolower(trim($value));

        if (preg_match('/^#([0-9a-f]{3})$/', $value, $short) === 1) {
            $chars = str_split($short[1]);

            return '#' . $chars[0] . $chars[0] . $chars[1] . $chars[1] . $chars[2] . $chars[2];
        }

        return preg_match('/^#[0-9a-f]{6}$/', $value) === 1 ? $value : null;
    }

    /**
     * @return array{0: float, 1: float, 2: float} lightness, chroma, hue (degrees)
     */
    public static function toOklch(string $hex): array
    {
        [$red, $green, $blue] = array_map(self::toLinear(...), self::hexToRgb($hex));

        $long = 0.4122214708 * $red + 0.5363325363 * $green + 0.0514459929 * $blue;
        $medium = 0.2119034982 * $red + 0.6806995451 * $green + 0.1073969566 * $blue;
        $short = 0.0883024619 * $red + 0.2817188376 * $green + 0.6299787005 * $blue;

        $long = self::cubeRoot($long);
        $medium = self::cubeRoot($medium);
        $short = self::cubeRoot($short);

        $lightness = 0.2104542553 * $long + 0.793617785 * $medium - 0.0040720468 * $short;
        $opponentA = 1.9779984951 * $long - 2.428592205 * $medium + 0.4505937099 * $short;
        $opponentB = 0.0259040371 * $long + 0.7827717662 * $medium - 0.808675766 * $short;

        $chroma = hypot($opponentA, $opponentB);
        $hue = fmod(rad2deg(atan2($opponentB, $opponentA)) + 360.0, 360.0);

        return [$lightness, $chroma, $hue];
    }

    /**
     * OKLCH to `#rrggbb`; a colour the screen cannot show loses chroma until it can.
     */
    public static function fromOklch(float $lightness, float $chroma, float $hue): string
    {
        $chroma = max(0.0, $chroma);

        if (! self::isInGamut(self::oklchToLinear($lightness, $chroma, $hue))) {
            $low = 0.0;
            $high = $chroma;

            for ($i = 0; $i < 24; $i++) {
                $mid = ($low + $high) / 2;

                if (self::isInGamut(self::oklchToLinear($lightness, $mid, $hue))) {
                    $low = $mid;
                } else {
                    $high = $mid;
                }
            }

            $chroma = $low;
        }

        $rgb = array_map(
            static fn (float $channel): int => (int) round(min(1.0, max(0.0, self::fromLinear($channel))) * 255),
            self::oklchToLinear($lightness, $chroma, $hue),
        );

        return sprintf('#%02x%02x%02x', ...$rgb);
    }

    /**
     * True when the OKLCH colour fits sRGB without clipping.
     */
    public static function fitsSrgb(float $lightness, float $chroma, float $hue): bool
    {
        return self::isInGamut(self::oklchToLinear($lightness, $chroma, $hue));
    }

    /**
     * WCAG contrast ratio of two `#rrggbb` colours.
     */
    public static function contrast(string $first, string $second): float
    {
        $a = self::luminance($first);
        $b = self::luminance($second);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    /**
     * Shades 50–950 of $hex, step 600 being $hex itself unless white text on it would not read.
     */
    public static function palette(string $hex): ColorPalette
    {
        $hex = self::normalizeHex($hex) ?? throw new \InvalidArgumentException("Not a hex colour: {$hex}");

        [$originalLightness, $chroma, $hue] = self::toOklch($hex);

        $lightness = max($originalLightness, self::LIGHTNESS_FLOOR);
        $lifted = $lightness > $originalLightness + 0.0005;
        $base = $lifted ? self::fromOklch($lightness, $chroma, $hue) : $hex;

        while (self::contrast('#ffffff', $base) < self::MIN_CONTRAST && $lightness - self::DARKEN_STEP >= self::LIGHTNESS_FLOOR) {
            $lightness -= self::DARKEN_STEP;
            $base = self::fromOklch($lightness, $chroma, $hue);
        }

        $adjusted = $lightness < $originalLightness - 0.0005;

        $lightnesses = [
            0.97,
            0.94,
            0.895,
            0.835,
            min($lightness + 0.17, 0.8),
            min($lightness + 0.075, 0.74),
            $lightness,
            max($lightness - 0.055, 0.25),
            max($lightness - 0.11, 0.22),
            max($lightness - 0.155, 0.19),
            max($lightness - 0.2, 0.15),
        ];

        $shades = [];

        foreach (self::STEPS as $index => $step) {
            $shades[$step] = $step === 600
                ? $base
                : self::fromOklch($lightnesses[$index], $chroma * self::CHROMA[$index], $hue);
        }

        return new ColorPalette($shades, $adjusted, $lifted);
    }

    /**
     * Filament's grey (zinc) lightness, tinted towards the primary hue; a grey primary gives pure grey.
     *
     * @return array<int, string>
     */
    public static function grays(string $primary): array
    {
        [, $chroma, $hue] = self::toOklch(self::normalizeHex($primary) ?? '#000000');

        $tint = $chroma < self::ACHROMATIC_CHROMA ? 0.0 : min($chroma, 0.2) / 0.2 * self::GRAY_TINT_CHROMA;

        $shades = [];

        foreach (self::STEPS as $step) {
            [$lightness] = self::parseOklch(Color::Zinc[$step]);
            // The darkest greys carry a little less tint, as zinc does.
            $shades[$step] = self::fromOklch($lightness, $tint * ($step >= 800 ? 0.8 : 1.0), $hue);
        }

        return $shades;
    }

    /**
     * The second colour: the primary hue minus 40°, at a moderate chroma.
     *
     * @return array<int, string>
     */
    public static function accent(string $primary): array
    {
        [, $chroma, $hue] = self::toOklch(self::normalizeHex($primary) ?? '#000000');

        $accentHue = fmod($hue - self::ACCENT_HUE_SHIFT + 360.0, 360.0);

        return self::palette(self::fromOklch(0.6, min($chroma, 0.15), $accentHue))->shades;
    }

    /**
     * The primary sits so close to the danger red that buttons could be mistaken for delete.
     */
    public static function isNearDanger(string $primary): bool
    {
        [, $chroma, $hue] = self::toOklch(self::normalizeHex($primary) ?? '#000000');

        return $chroma > 0.09 && self::hueGap($hue, self::dangerHue()) < self::NEAR_DANGER_DEGREES;
    }

    /**
     * Hue of Filament's danger red at the step danger buttons use (600, about 27°).
     */
    public static function dangerHue(): float
    {
        return self::parseOklch(Color::Red[600])[2];
    }

    public static function hueGap(float $first, float $second): float
    {
        $gap = abs($first - $second);

        return min($gap, 360.0 - $gap);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private static function parseOklch(string $color): array
    {
        $parsed = sscanf($color, 'oklch(%f %f %f)');

        return [(float) ($parsed[0] ?? 0), (float) ($parsed[1] ?? 0), (float) ($parsed[2] ?? 0)];
    }

    /**
     * @return array{0: float, 1: float, 2: float} 0–1 channels
     */
    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim(self::normalizeHex($hex) ?? '#000000', '#');

        return [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];
    }

    private static function luminance(string $hex): float
    {
        [$red, $green, $blue] = array_map(self::toLinear(...), self::hexToRgb($hex));

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    }

    /**
     * @return array{0: float, 1: float, 2: float} linear sRGB
     */
    private static function oklchToLinear(float $lightness, float $chroma, float $hue): array
    {
        $opponentA = $chroma * cos(deg2rad($hue));
        $opponentB = $chroma * sin(deg2rad($hue));

        $long = ($lightness + 0.3963377774 * $opponentA + 0.2158037573 * $opponentB) ** 3;
        $medium = ($lightness - 0.1055613458 * $opponentA - 0.0638541728 * $opponentB) ** 3;
        $short = ($lightness - 0.0894841775 * $opponentA - 1.291485548 * $opponentB) ** 3;

        return [
            4.0767416621 * $long - 3.3077115913 * $medium + 0.2309699292 * $short,
            -1.2684380046 * $long + 2.6097574011 * $medium - 0.3413193965 * $short,
            -0.0041960863 * $long - 0.7034186147 * $medium + 1.707614701 * $short,
        ];
    }

    /**
     * @param  array{0: float, 1: float, 2: float}  $linear
     */
    private static function isInGamut(array $linear): bool
    {
        foreach ($linear as $channel) {
            if ($channel < -0.0005 || $channel > 1.0005) {
                return false;
            }
        }

        return true;
    }

    private static function toLinear(float $channel): float
    {
        return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
    }

    private static function fromLinear(float $channel): float
    {
        return $channel <= 0.0031308 ? $channel * 12.92 : 1.055 * $channel ** (1 / 2.4) - 0.055;
    }

    private static function cubeRoot(float $value): float
    {
        return $value < 0 ? -((-$value) ** (1 / 3)) : $value ** (1 / 3);
    }
}
