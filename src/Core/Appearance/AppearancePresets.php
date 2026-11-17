<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Appearance;

/**
 * Ready looks and ready colours.
 *
 * A ready look fills colour, sidebar, corners, field shape and label place only; mode, text size and
 * density stay as the person has them. "Modern" is the app default, so every one of its parts is empty.
 */
final class AppearancePresets
{
    public const string SAVED_PREFIX = 'saved:';

    /**
     * @var array<string, array{primary?: string, rail?: string, radius?: string, field?: string, label?: string}>
     */
    public const array PRESETS = [
        'modern' => [],
        'classic' => ['primary' => '#1e40af', 'rail' => 'light', 'radius' => 'sharp', 'field' => 'outlined', 'label' => 'side'],
        'minimal' => ['primary' => '#475569', 'rail' => 'light', 'radius' => 'sharp', 'field' => 'underline', 'label' => 'top'],
        'soft' => ['primary' => '#0e7490', 'rail' => 'brand', 'radius' => 'round', 'field' => 'floating', 'label' => 'top'],
        'bold' => ['primary' => '#7c3aed', 'rail' => 'brand', 'radius' => 'round', 'field' => 'outlined', 'label' => 'top'],
    ];

    /** Parts a ready look sets (or clears); the rest stay as they are. */
    public const array PRESET_PARTS = ['primary', 'rail', 'radius', 'field', 'label'];

    /**
     * Ready colours; each already reads well under white text.
     *
     * @var array<string, string>
     */
    public const array COLORS = [
        'indigo' => '#4f46e5',
        'blue' => '#2563eb',
        'cyan' => '#0e7490',
        'emerald' => '#047857',
        'violet' => '#7c3aed',
        'orange' => '#c2410c',
        'fuchsia' => '#a21caf',
        'slate' => '#475569',
    ];

    public static function preset(string $key): Appearance
    {
        return Appearance::fromArray(['preset' => $key] + (self::PRESETS[$key] ?? []));
    }

    /**
     * Apply a ready look on top of $current: preset parts are replaced (or cleared), the rest kept.
     */
    public static function apply(Appearance $current, Appearance $look, string $presetKey): Appearance
    {
        $state = $current->toArray();
        $incoming = $look->toArray();

        foreach (self::PRESET_PARTS as $part) {
            unset($state[$part]);

            if (isset($incoming[$part])) {
                $state[$part] = $incoming[$part];
            }
        }

        $state['preset'] = $presetKey;

        return Appearance::fromArray($state);
    }

    /**
     * A valid preset marker (`modern`… or `saved:{name}`), or null.
     */
    public static function normalizePresetKey(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        if (array_key_exists($value, self::PRESETS)) {
            return $value;
        }

        if (str_starts_with($value, self::SAVED_PREFIX)) {
            $name = substr($value, strlen(self::SAVED_PREFIX));

            return ($name !== '' && mb_strlen($name) <= AppearanceRepository::MAX_NAME_LENGTH) ? $value : null;
        }

        return null;
    }
}
