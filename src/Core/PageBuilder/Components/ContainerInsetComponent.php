<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

/**
 * Wraps nested blocks with optional max width and optional full-bleed background.
 */
final class ContainerInsetComponent extends BaseBuilderComponent
{
    private const GRADIENT_MAX_LENGTH = 800;

    public static function getType(): string
    {
        return 'container_inset';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_container_inset');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-arrows-pointing-in';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_LAYOUT;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_container_inset');
    }

    public static function getSchema(): array
    {
        return [
            Select::make('max_width')
                ->label(__('mksine::page_builder.component_labels.container_inset_max_width'))
                ->options(static::contentScaleSelectOptions())
                ->default('full')
                ->required()
                ->native(false),
            Toggle::make('background_full_bleed')
                ->label(__('mksine::page_builder.component_labels.container_inset_background_full_bleed'))
                ->helperText(__('mksine::page_builder.component_labels.container_inset_background_full_bleed_hint'))
                ->default(false),
            ColorPicker::make('background_color')
                ->label(__('mksine::page_builder.component_labels.container_inset_background_color'))
                ->helperText(__('mksine::page_builder.component_labels.container_inset_background_color_hint'))
                ->hex()
                ->nullable(),
            Textarea::make('background_gradient')
                ->label(__('mksine::page_builder.component_labels.container_inset_background_gradient'))
                ->helperText(__('mksine::page_builder.component_labels.container_inset_background_gradient_hint'))
                ->rows(3)
                ->maxLength(self::GRADIENT_MAX_LENGTH)
                ->nullable()
                ->columnSpanFull(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'max_width' => 'full',
            'background_full_bleed' => false,
            'background_color' => null,
            'background_gradient' => null,
        ];
    }

    public static function supportsChildren(): bool
    {
        return true;
    }

    public static function getMaxChildren(): ?int
    {
        return 1;
    }

    public static function getBuilderChildRegionLabel(int $columnIndex, int $columnCount): string
    {
        return __('mksine::page_builder.container_inset_nested_label');
    }

    public static function createInstance(?string $id = null): array
    {
        $instance = parent::createInstance($id);
        $instance['children'] = [
            [
                'id' => uniqid('col_'),
                'items' => [],
            ],
        ];

        return $instance;
    }

    /**
     * Labels/options for max content width (Tailwind max-width scale).
     *
     * @return array<string, string>
     */
    protected static function contentScaleSelectOptions(): array
    {
        return [
            'full' => __('mksine::page_builder.component_labels.container_inset_width_full'),
            'prose' => __('mksine::page_builder.component_labels.container_inset_width_prose'),
            '3xl' => __('mksine::page_builder.component_labels.container_inset_width_3xl'),
            '5xl' => __('mksine::page_builder.component_labels.container_inset_width_5xl'),
            '6xl' => __('mksine::page_builder.component_labels.container_inset_width_6xl'),
            '7xl' => __('mksine::page_builder.component_labels.container_inset_width_7xl'),
        ];
    }

    /**
     * Clamp max_width to known layout keys.
     */
    private static function normalizeMaxWidth(string $key): string
    {
        $allowed = ['full', 'prose', '3xl', '5xl', '6xl', '7xl'];

        return in_array($key, $allowed, true) ? $key : 'full';
    }

    /**
     * Normalize optional inset background: CSS hex only (#rgb, #rrggbb, #rrggbbaa).
     */
    public static function normalizedBackgroundColor(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $trimmed) !== 1) {
            return null;
        }

        return $trimmed;
    }

    /**
     * Normalize optional CSS gradient() value for background-image (single declaration, no escapes).
     */
    public static function normalizedBackgroundGradient(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (strlen($trimmed) > self::GRADIENT_MAX_LENGTH) {
            return null;
        }

        if (preg_match('/[\r\n]/', $trimmed) === 1) {
            return null;
        }

        if (str_contains($trimmed, ';')) {
            return null;
        }

        if (str_contains($trimmed, '\\')) {
            return null;
        }

        $blocked = ['url(', 'expression(', '@import', 'javascript:', '<script', '</'];
        $lower = strtolower($trimmed);
        foreach ($blocked as $needle) {
            if (str_contains($lower, $needle)) {
                return null;
            }
        }

        if (preg_match(
            '/^(?:linear|radial|conic|repeating-linear|repeating-radial)-gradient\s*\(/i',
            $trimmed
        ) !== 1) {
            return null;
        }

        if (! str_ends_with($trimmed, ')')) {
            return null;
        }

        if (substr_count($trimmed, '(') !== substr_count($trimmed, ')')) {
            return null;
        }

        return $trimmed;
    }

    public static function validate(array $data): array
    {
        unset($data['padding_inline']);

        $m = (string) ($data['max_width'] ?? 'full');
        $data['max_width'] = static::normalizeMaxWidth($m);

        $data['background_full_bleed'] = filter_var($data['background_full_bleed'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $data['background_color'] = static::normalizedBackgroundColor($data['background_color'] ?? null);

        $data['background_gradient'] = static::normalizedBackgroundGradient($data['background_gradient'] ?? null);

        return $data;
    }
}
