<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Appearance;

use BackedEnum;

/**
 * One layer of the admin look: every part is optional and an empty part falls through to the
 * layer below (app default ← company ← personal).
 *
 * {@see fromArray()} never throws: an unknown key, a value outside the allow-list or a bad hex
 * is read as "empty", so a corrupt or outdated stored row degrades to the default look.
 *
 * @phpstan-type AppearanceArray array{preset?: string, primary?: string, mode?: string, rail?: string, radius?: string, font?: string, density?: string, field?: string, label?: string}
 */
final readonly class Appearance
{
    /** Every part, in display order. */
    public const array PARTS = ['preset', 'primary', 'mode', 'rail', 'radius', 'font', 'density', 'field', 'label'];

    /** Parts backed by an enum. */
    public const array ENUM_PARTS = [
        'mode' => AppearanceMode::class,
        'rail' => AppearanceRail::class,
        'radius' => AppearanceRadius::class,
        'font' => AppearanceFont::class,
        'density' => AppearanceDensity::class,
        'field' => AppearanceField::class,
        'label' => AppearanceLabel::class,
    ];

    public function __construct(
        public ?string $preset = null,
        public ?string $primary = null,
        public ?AppearanceMode $mode = null,
        public ?AppearanceRail $rail = null,
        public ?AppearanceRadius $radius = null,
        public ?AppearanceFont $font = null,
        public ?AppearanceDensity $density = null,
        public ?AppearanceField $field = null,
        public ?AppearanceLabel $label = null,
    ) {}

    public static function empty(): self
    {
        return new self;
    }

    /**
     * Build a layer from untrusted input (stored JSON, Livewire state), dropping anything invalid.
     */
    public static function fromArray(mixed $data): self
    {
        if (! is_array($data)) {
            return new self;
        }

        $enums = [];

        foreach (self::ENUM_PARTS as $part => $enumClass) {
            $value = $data[$part] ?? null;
            $enums[$part] = (is_string($value) || is_int($value)) ? $enumClass::tryFrom((string) $value) : null;
        }

        return new self(
            preset: AppearancePresets::normalizePresetKey($data['preset'] ?? null),
            primary: ColorScale::normalizeHex($data['primary'] ?? null),
            mode: $enums['mode'],
            rail: $enums['rail'],
            radius: $enums['radius'],
            font: $enums['font'],
            density: $enums['density'],
            field: $enums['field'],
            label: $enums['label'],
        );
    }

    public static function fromJson(?string $json): self
    {
        if ($json === null || $json === '') {
            return new self;
        }

        return self::fromArray(json_decode($json, true));
    }

    /**
     * Filled parts only, as plain strings.
     *
     * @return AppearanceArray
     */
    public function toArray(): array
    {
        $out = [];

        foreach (self::PARTS as $part) {
            $value = $this->{$part};

            if ($value instanceof BackedEnum) {
                $value = (string) $value->value;
            }

            if (is_string($value) && $value !== '') {
                $out[$part] = $value;
            }
        }

        /** @var AppearanceArray $out */
        return $out;
    }

    /**
     * Every part as a string or null (for form state).
     *
     * @return array<string, string|null>
     */
    public function toState(): array
    {
        $filled = $this->toArray();
        $state = [];

        foreach (self::PARTS as $part) {
            $state[$part] = $filled[$part] ?? null;
        }

        return $state;
    }

    /**
     * True when no visual part is set (the preset marker alone changes nothing).
     */
    public function isEmpty(): bool
    {
        $parts = $this->toArray();
        unset($parts['preset']);

        return $parts === [];
    }

    /**
     * This layer laid over $base: every filled part here wins.
     */
    public function over(self $base): self
    {
        return self::fromArray(array_merge($base->toArray(), $this->toArray()));
    }

    /**
     * This layer minus every part that already equals $base (what a personal look stores).
     */
    public function withoutPartsOf(self $base): self
    {
        $mine = $this->toArray();
        $theirs = $base->toArray();

        foreach ($mine as $part => $value) {
            if ($part !== 'preset' && ($theirs[$part] ?? null) === $value) {
                unset($mine[$part]);
            }
        }

        if (count($mine) === 1 && isset($mine['preset'])) {
            $mine = [];
        }

        return self::fromArray($mine);
    }

    public function equals(self $other): bool
    {
        return $this->toArray() === $other->toArray();
    }
}
