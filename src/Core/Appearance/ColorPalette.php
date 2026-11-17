<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Appearance;

/**
 * Shades 50–950 built from one colour by {@see ColorScale::palette()}.
 */
final readonly class ColorPalette
{
    /**
     * @param  array<int, string>  $shades  step => `#rrggbb`
     * @param  bool  $adjusted  Step 600 was darkened so white text on it reads (4.5:1).
     * @param  bool  $lifted  A near-black colour was lifted to the lightness floor.
     */
    public function __construct(
        public array $shades,
        public bool $adjusted,
        public bool $lifted,
    ) {}

    /**
     * The colour buttons and links use.
     */
    public function base(): string
    {
        return $this->shades[600];
    }
}
