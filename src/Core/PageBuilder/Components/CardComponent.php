<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;
use Miran\Mksine\Core\PageBuilder\BlockPresentation;

/**
 * Header, body, and footer bands. Header and footer can be hidden without deleting their blocks.
 */
final class CardComponent extends BaseBuilderComponent
{
    public const HEADER = 0;

    public const BODY = 1;

    public const FOOTER = 2;

    public static function getType(): string
    {
        return 'card';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_card');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-square-2-stack';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_LAYOUT;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_card');
    }

    public static function getSchema(): array
    {
        return [
            Toggle::make('show_header')
                ->label(__('mksine::page_builder.component_labels.card_show_header'))
                ->helperText(__('mksine::page_builder.component_labels.card_content_hint'))
                ->default(true)
                ->live(),
            Toggle::make('show_footer')
                ->label(__('mksine::page_builder.component_labels.card_show_footer'))
                ->default(false)
                ->live(),
            Section::make(__('mksine::page_builder.component_labels.card_style'))
                ->schema(self::surfaceFields())
                ->columns(2)
                ->columnSpanFull(),
            Section::make(__('mksine::page_builder.component_labels.card_header'))
                ->schema(self::bandFields('header'))
                ->columns(2)
                ->columnSpanFull()
                ->visible(fn ($get): bool => self::enabled($get('show_header'), true)),
            Section::make(__('mksine::page_builder.component_labels.card_body'))
                ->schema([
                    ColorPicker::make('body_background')
                        ->label(__('mksine::page_builder.component_labels.background_color'))
                        ->hex()
                        ->nullable(),
                    self::paddingField('body_padding'),
                ])
                ->columns(2)
                ->columnSpanFull(),
            Section::make(__('mksine::page_builder.component_labels.card_footer'))
                ->schema(self::bandFields('footer'))
                ->columns(2)
                ->columnSpanFull()
                ->visible(fn ($get): bool => self::enabled($get('show_footer'), false)),
        ];
    }

    public static function getDefaultData(): array
    {
        return self::validate([]);
    }

    public static function validate(array $data): array
    {
        return [
            'show_header' => self::enabled($data['show_header'] ?? null, true),
            'show_footer' => self::enabled($data['show_footer'] ?? null, false),
            'background_color' => BlockPresentation::color($data['background_color'] ?? ''),
            'text_color' => BlockPresentation::color($data['text_color'] ?? ''),
            'border_color' => BlockPresentation::color($data['border_color'] ?? ''),
            'border_width' => self::enum($data['border_width'] ?? null, ['none', 'sm', 'md'], 'sm'),
            'radius' => self::enum($data['radius'] ?? null, ['none', 'sm', 'md', 'lg'], 'md'),
            'shadow' => self::enum($data['shadow'] ?? null, ['none', 'sm', 'md'], 'sm'),
            'header_background' => BlockPresentation::color($data['header_background'] ?? ''),
            'header_color' => BlockPresentation::color($data['header_color'] ?? ''),
            'header_align' => self::enum($data['header_align'] ?? null, ['start', 'center', 'end'], 'start'),
            'header_padding' => self::enum($data['header_padding'] ?? null, ['none', 'sm', 'md', 'lg'], 'md'),
            'body_background' => BlockPresentation::color($data['body_background'] ?? ''),
            'body_padding' => self::enum($data['body_padding'] ?? null, ['none', 'sm', 'md', 'lg'], 'md'),
            'footer_background' => BlockPresentation::color($data['footer_background'] ?? ''),
            'footer_color' => BlockPresentation::color($data['footer_color'] ?? ''),
            'footer_align' => self::enum($data['footer_align'] ?? null, ['start', 'center', 'end'], 'start'),
            'footer_padding' => self::enum($data['footer_padding'] ?? null, ['none', 'sm', 'md', 'lg'], 'md'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{show_header: bool, show_footer: bool, card: string, header: string, body: string, footer: string}
     */
    public static function styles(array $data): array
    {
        $data = self::validate($data);
        $radius = match ($data['radius']) {
            'none' => '0',
            'sm' => '0.375rem',
            'lg' => '1.25rem',
            default => '0.75rem',
        };
        $borderWidth = match ($data['border_width']) {
            'md' => '2px',
            'sm' => '1px',
            default => '0',
        };
        $shadow = match ($data['shadow']) {
            'md' => '0 10px 24px rgb(15 23 42 / 0.08)',
            'sm' => '0 1px 2px rgb(15 23 42 / 0.06)',
            default => 'none',
        };
        $line = $data['border_color'] !== '' ? $data['border_color'] : '#e4e4e7';
        $card = [
            'background-color: '.($data['background_color'] !== '' ? $data['background_color'] : '#ffffff'),
            'border-radius: '.$radius,
            'overflow: hidden',
            'box-shadow: '.$shadow,
        ];
        if ($data['text_color'] !== '') {
            $card[] = 'color: '.$data['text_color'];
        }
        if ($borderWidth !== '0') {
            $card[] = 'border: '.$borderWidth.' solid '.$line;
        }

        return [
            'show_header' => $data['show_header'],
            'show_footer' => $data['show_footer'],
            'card' => implode('; ', $card),
            'header' => self::bandStyle($data, 'header', $line),
            'body' => self::bodyStyle($data),
            'footer' => self::bandStyle($data, 'footer', $line),
        ];
    }

    public static function supportsChildren(): bool
    {
        return true;
    }

    public static function getMaxChildren(): ?int
    {
        return 3;
    }

    public static function getBuilderChildRegionLabel(int $columnIndex, int $columnCount): string
    {
        return match ($columnIndex) {
            self::HEADER => __('mksine::page_builder.component_labels.card_header'),
            self::FOOTER => __('mksine::page_builder.component_labels.card_footer'),
            default => __('mksine::page_builder.component_labels.card_body'),
        };
    }

    public static function createInstance(?string $id = null): array
    {
        $instance = parent::createInstance($id);
        $instance['children'] = [];
        foreach ([self::HEADER, self::BODY, self::FOOTER] as $index) {
            $instance['children'][$index] = [
                'id' => uniqid('col_'),
                'items' => [],
            ];
        }

        return $instance;
    }

    /**
     * @return list<ColorPicker|Select>
     */
    private static function surfaceFields(): array
    {
        return [
            ColorPicker::make('background_color')
                ->label(__('mksine::page_builder.component_labels.background_color'))
                ->hex()
                ->nullable(),
            ColorPicker::make('text_color')
                ->label(__('mksine::page_builder.component_labels.text_color'))
                ->hex()
                ->nullable(),
            ColorPicker::make('border_color')
                ->label(__('mksine::page_builder.component_labels.border_color'))
                ->hex()
                ->nullable(),
            Select::make('border_width')
                ->label(__('mksine::page_builder.component_labels.border_width'))
                ->options(self::scaleOptions(['none', 'sm', 'md']))
                ->default('sm')
                ->native(false),
            Select::make('radius')
                ->label(__('mksine::page_builder.component_labels.border_radius'))
                ->options(self::scaleOptions(['none', 'sm', 'md', 'lg']))
                ->default('md')
                ->native(false),
            Select::make('shadow')
                ->label(__('mksine::page_builder.component_labels.shadow'))
                ->options(self::scaleOptions(['none', 'sm', 'md']))
                ->default('sm')
                ->native(false),
        ];
    }

    /**
     * @return list<ColorPicker|Select>
     */
    private static function bandFields(string $band): array
    {
        return [
            ColorPicker::make($band.'_background')
                ->label(__('mksine::page_builder.component_labels.background_color'))
                ->hex()
                ->nullable(),
            ColorPicker::make($band.'_color')
                ->label(__('mksine::page_builder.component_labels.text_color'))
                ->hex()
                ->nullable(),
            Select::make($band.'_align')
                ->label(__('mksine::page_builder.component_labels.alignment'))
                ->options([
                    'start' => __('mksine::page_builder.component_labels.start'),
                    'center' => __('mksine::page_builder.component_labels.center'),
                    'end' => __('mksine::page_builder.component_labels.end'),
                ])
                ->default('start')
                ->native(false),
            self::paddingField($band.'_padding'),
        ];
    }

    private static function paddingField(string $name): Select
    {
        return Select::make($name)
            ->label(__('mksine::page_builder.component_labels.padding'))
            ->options(self::scaleOptions(['none', 'sm', 'md', 'lg']))
            ->default('md')
            ->native(false);
    }

    /**
     * @param  list<string>  $keys
     * @return array<string, string>
     */
    private static function scaleOptions(array $keys): array
    {
        $labels = [
            'none' => __('mksine::page_builder.component_labels.none'),
            'sm' => __('mksine::page_builder.component_labels.small'),
            'md' => __('mksine::page_builder.component_labels.medium'),
            'lg' => __('mksine::page_builder.component_labels.large'),
        ];

        return array_intersect_key($labels, array_flip($keys));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function bandStyle(array $data, string $band, string $line): string
    {
        $padding = match ($data[$band.'_padding']) {
            'none' => '0',
            'sm' => '0.5rem 0.75rem',
            'lg' => '1.25rem 1.5rem',
            default => '0.875rem 1.25rem',
        };
        $align = match ($data[$band.'_align']) {
            'center' => 'center',
            'end' => 'end',
            default => 'start',
        };
        $rules = [
            'padding: '.$padding,
            'text-align: '.$align,
            'background-color: '.($data[$band.'_background'] !== '' ? $data[$band.'_background'] : '#fafafa'),
            ($band === 'header' ? 'border-bottom' : 'border-top').': 1px solid '.$line,
        ];
        if ($data[$band.'_color'] !== '') {
            $rules[] = 'color: '.$data[$band.'_color'];
        }

        return implode('; ', $rules);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function bodyStyle(array $data): string
    {
        $padding = match ($data['body_padding']) {
            'none' => '0',
            'sm' => '0.75rem 1rem',
            'lg' => '1.5rem',
            default => '1rem 1.25rem',
        };
        $rules = ['padding: '.$padding];
        if ($data['body_background'] !== '') {
            $rules[] = 'background-color: '.$data['body_background'];
        }

        return implode('; ', $rules);
    }

    /**
     * @param  list<string>  $allowed
     */
    private static function enum(mixed $value, array $allowed, string $default): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : $default;
    }

    private static function enabled(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
