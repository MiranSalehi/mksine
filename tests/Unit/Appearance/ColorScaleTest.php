<?php

declare(strict_types=1);

use Miran\Mksine\Core\Appearance\AppearancePresets;
use Miran\Mksine\Core\Appearance\ColorScale;

/**
 * Colours that are hard to make readable under white text.
 *
 * @return array<string, array{string}>
 */
function appearanceHardColors(): array
{
    return [
        'yellow' => ['#facc15'],
        'white' => ['#ffffff'],
        'black' => ['#000000'],
        'light gray' => ['#e5e7eb'],
        'lime' => ['#84cc16'],
        'light cyan' => ['#22d3ee'],
        'pale pink' => ['#fbcfe8'],
        'neon green' => ['#00ff00'],
        'pure blue' => ['#0000ff'],
        'pure red' => ['#ff0000'],
    ];
}

/**
 * @return array<string, array{string}>
 */
function appearanceAllColors(): array
{
    $ready = [];

    foreach (AppearancePresets::COLORS as $name => $hex) {
        $ready[$name] = [$hex];
    }

    return $ready + appearanceHardColors();
}

test('hex input is normalized and bad hex is rejected', function (): void {
    expect(ColorScale::normalizeHex('#0aF'))->toBe('#00aaff')
        ->and(ColorScale::normalizeHex(' #4F46E5 '))->toBe('#4f46e5')
        ->and(ColorScale::normalizeHex('4f46e5'))->toBeNull()
        ->and(ColorScale::normalizeHex('#4f46e5ff'))->toBeNull()
        ->and(ColorScale::normalizeHex('#ggg'))->toBeNull()
        ->and(ColorScale::normalizeHex('red'))->toBeNull()
        ->and(ColorScale::normalizeHex(''))->toBeNull()
        ->and(ColorScale::normalizeHex(null))->toBeNull()
        ->and(ColorScale::normalizeHex(['#fff']))->toBeNull();
});

test('a palette rejects a bad hex', function (): void {
    ColorScale::palette('not-a-colour');
})->throws(InvalidArgumentException::class);

test('hex survives the round trip through OKLCH', function (string $hex): void {
    [$lightness, $chroma, $hue] = ColorScale::toOklch($hex);

    expect(ColorScale::fromOklch($lightness, $chroma, $hue))->toBe($hex);
})->with(appearanceAllColors());

test('shades run from light to dark', function (string $hex): void {
    $lightness = array_map(static fn (string $shade): float => ColorScale::toOklch($shade)[0], ColorScale::palette($hex)->shades);

    expect(array_keys($lightness))->toBe(ColorScale::STEPS);

    $previous = INF;

    foreach ($lightness as $step => $value) {
        expect($value)->toBeLessThan($previous, "step {$step} of {$hex}");
        $previous = $value;
    }
})->with(appearanceAllColors());

test('white text on step 600 reaches 4.5:1', function (string $hex): void {
    expect(ColorScale::contrast('#ffffff', ColorScale::palette($hex)->base()))->toBeGreaterThanOrEqual(ColorScale::MIN_CONTRAST);
})->with(appearanceAllColors());

test('ready colours are used exactly as chosen', function (string $hex): void {
    $palette = ColorScale::palette($hex);

    expect($palette->base())->toBe($hex)
        ->and($palette->adjusted)->toBeFalse()
        ->and($palette->lifted)->toBeFalse();
})->with(array_map(static fn (string $hex): array => [$hex], AppearancePresets::COLORS));

test('only orange among the ready colours sits near the danger red', function (): void {
    $near = array_keys(array_filter(AppearancePresets::COLORS, ColorScale::isNearDanger(...)));

    // Filament's danger red has hue ~27°; #c2410c sits at ~38°, 11° away, inside the 14° rule.
    expect($near)->toBe(['orange']);
});

test('a light colour is darkened and says so', function (): void {
    $palette = ColorScale::palette('#facc15');

    expect($palette->adjusted)->toBeTrue()
        ->and($palette->base())->not->toBe('#facc15')
        ->and(ColorScale::toOklch($palette->base())[2])->toEqualWithDelta(ColorScale::toOklch('#facc15')[2], 3.0);
});

test('black is lifted to the lightness floor and still leaves room for darker steps', function (): void {
    $palette = ColorScale::palette('#000000');

    expect($palette->lifted)->toBeTrue()
        ->and($palette->adjusted)->toBeFalse()
        ->and(ColorScale::toOklch($palette->base())[0])->toEqualWithDelta(ColorScale::LIGHTNESS_FLOOR, 0.01)
        ->and(ColorScale::toOklch($palette->shades[950])[0])->toBeLessThan(ColorScale::toOklch($palette->shades[700])[0]);
});

test('step 600 never drops below the lightness floor', function (string $hex): void {
    expect(ColorScale::toOklch(ColorScale::palette($hex)->base())[0])->toBeGreaterThanOrEqual(ColorScale::LIGHTNESS_FLOOR - 0.005);
})->with(appearanceAllColors());

test('every shade fits sRGB', function (string $hex): void {
    foreach (ColorScale::palette($hex)->shades + ColorScale::grays($hex) + ColorScale::accent($hex) as $shade) {
        [$lightness, $chroma, $hue] = ColorScale::toOklch($shade);

        expect(ColorScale::fitsSrgb($lightness, $chroma, $hue))->toBeTrue();
    }
})->with(appearanceAllColors());

test('out-of-gamut colours lose chroma, not lightness or hue', function (): void {
    $hex = ColorScale::fromOklch(0.7, 0.4, 145.0);
    [$lightness, $chroma, $hue] = ColorScale::toOklch($hex);

    expect($lightness)->toEqualWithDelta(0.7, 0.01)
        ->and($hue)->toEqualWithDelta(145.0, 2.0)
        ->and($chroma)->toBeLessThan(0.4);
});

test('greys lean towards the primary hue, and a grey primary gives pure grey', function (): void {
    $tinted = ColorScale::grays('#4f46e5');
    [, $chroma, $hue] = ColorScale::toOklch($tinted[500]);

    expect($chroma)->toBeGreaterThan(0.005)
        ->and(ColorScale::hueGap($hue, ColorScale::toOklch('#4f46e5')[2]))->toBeLessThan(10.0);

    foreach (ColorScale::grays('#808080') as $shade) {
        expect(ColorScale::toOklch($shade)[1])->toBeLessThan(0.003);
    }
});

test('the second colour sits 40 degrees below the primary hue', function (): void {
    [, , $primaryHue] = ColorScale::toOklch('#4f46e5');
    [, , $accentHue] = ColorScale::toOklch(ColorScale::accent('#4f46e5')[600]);

    expect(ColorScale::hueGap($accentHue, fmod($primaryHue - 40 + 360, 360)))->toBeLessThan(4.0);
});

test('a red primary warns about the danger colour', function (): void {
    expect(ColorScale::isNearDanger('#ef4444'))->toBeTrue()
        ->and(ColorScale::isNearDanger('#dc2626'))->toBeTrue()
        ->and(ColorScale::isNearDanger('#808080'))->toBeFalse();
});
