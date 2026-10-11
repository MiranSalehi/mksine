<?php

namespace Miran\Mksine\Core\PageBuilder;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

/**
 * Shared layout for every block. Stored beside data, not inside a component schema.
 */
final class BlockPresentation
{
    /**
     * @return array{anchor: string, spacing: string, visibility: string, padding: string, background: string, align: string, width: string}
     */
    public static function defaults(): array
    {
        return [
            'anchor' => '',
            'spacing' => 'none',
            'visibility' => 'always',
            'padding' => 'none',
            'background' => 'none',
            'align' => 'start',
            'width' => 'full',
            'text_color' => '',
            'background_color' => '',
            'border_width' => 'none',
            'border_color' => '',
            'border_radius' => 'none',
        ];
    }

    /**
     * @param  array<string, mixed>  $presentation
     * @return array{anchor: string, spacing: string, visibility: string, padding: string, background: string, align: string, width: string}
     */
    public static function normalize(array $presentation): array
    {
        $spacing = $presentation['spacing'] ?? 'none';
        $visibility = $presentation['visibility'] ?? 'always';
        $padding = $presentation['padding'] ?? 'none';
        $background = $presentation['background'] ?? 'none';
        $align = $presentation['align'] ?? 'start';
        $width = $presentation['width'] ?? 'full';
        $borderWidth = $presentation['border_width'] ?? 'none';
        $borderRadius = $presentation['border_radius'] ?? 'none';

        return [
            'anchor' => self::anchor($presentation['anchor'] ?? ''),
            'spacing' => in_array($spacing, ['none', 'sm', 'md', 'lg'], true) ? $spacing : 'none',
            'visibility' => in_array($visibility, ['always', 'hide_mobile', 'hide_desktop'], true) ? $visibility : 'always',
            'padding' => in_array($padding, ['none', 'sm', 'md', 'lg'], true) ? $padding : 'none',
            'background' => in_array($background, ['none', 'muted', 'brand'], true) ? $background : 'none',
            'align' => in_array($align, ['start', 'left', 'center', 'right', 'end'], true) ? $align : 'start',
            'width' => in_array($width, ['full', 'contained'], true) ? $width : 'full',
            'text_color' => self::color($presentation['text_color'] ?? ''),
            'background_color' => self::color($presentation['background_color'] ?? ''),
            'border_width' => in_array($borderWidth, ['none', 'sm', 'md', 'lg'], true) ? $borderWidth : 'none',
            'border_color' => self::color($presentation['border_color'] ?? ''),
            'border_radius' => in_array($borderRadius, ['none', 'sm', 'md', 'lg'], true) ? $borderRadius : 'none',
        ];
    }

    public static function anchor(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $value = strtolower(trim($value));
        $value = ltrim($value, '#');

        if (preg_match('/^[a-z][a-z0-9_-]{0,63}$/', $value) !== 1) {
            return '';
        }

        return $value;
    }

    public static function color(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        $value = trim($value);

        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) !== 1) {
            return '';
        }

        return strtolower($value);
    }

    /**
     * @param  array<string, mixed>  $presentation
     */
    public static function wrapperClass(array $presentation): string
    {
        $presentation = self::normalize($presentation);

        $visibility = match ($presentation['visibility']) {
            'hide_mobile' => 'hidden md:block',
            'hide_desktop' => 'md:hidden',
            default => '',
        };

        $width = match ($presentation['width']) {
            'contained' => 'mx-auto w-full max-w-4xl',
            default => '',
        };

        return trim($visibility.' '.$width);
    }

    /**
     * Inline rules so alignment, color, border, padding, and margin do not depend on the theme stylesheet.
     *
     * @param  array<string, mixed>  $presentation
     */
    public static function wrapperStyle(array $presentation): string
    {
        $presentation = self::normalize($presentation);
        $rules = [];

        $align = match ($presentation['align']) {
            'left' => 'left',
            'center' => 'center',
            'right' => 'right',
            'end' => 'end',
            default => null,
        };
        if ($align !== null) {
            $rules[] = 'text-align: '.$align;
        }

        if ($presentation['text_color'] !== '') {
            $rules[] = 'color: '.$presentation['text_color'];
        }

        if ($presentation['background_color'] !== '') {
            $rules[] = 'background-color: '.$presentation['background_color'];
        } elseif ($presentation['background'] === 'muted') {
            $rules[] = 'background-color: #f4f4f5';
        } elseif ($presentation['background'] === 'brand') {
            $rules[] = 'background-color: #f5f3ff';
        }

        $padding = match ($presentation['padding']) {
            'sm' => '0.5rem',
            'md' => '1rem',
            'lg' => '2rem',
            default => null,
        };
        if ($padding !== null) {
            $rules[] = 'padding: '.$padding;
        }

        $margin = match ($presentation['spacing']) {
            'sm' => '0.5rem',
            'md' => '1rem',
            'lg' => '2rem',
            default => null,
        };
        if ($margin !== null) {
            $rules[] = 'margin-top: '.$margin;
            $rules[] = 'margin-bottom: '.$margin;
        }

        $border = match ($presentation['border_width']) {
            'sm' => '1px',
            'md' => '2px',
            'lg' => '4px',
            default => null,
        };
        if ($border !== null) {
            $rules[] = 'border-style: solid';
            $rules[] = 'border-width: '.$border;
            $rules[] = 'border-color: '.($presentation['border_color'] !== '' ? $presentation['border_color'] : '#e4e4e7');
        }

        $radius = match ($presentation['border_radius']) {
            'sm' => '0.375rem',
            'md' => '0.75rem',
            'lg' => '1.25rem',
            default => null,
        };
        if ($radius !== null) {
            $rules[] = 'border-radius: '.$radius;
        }

        return implode('; ', $rules);
    }

    /**
     * @return list<Select|TextInput|ColorPicker>
     */
    public static function fields(): array
    {
        $scale = [
            'none' => __('mksine::page_builder.presentation_spacing_none'),
            'sm' => __('mksine::page_builder.presentation_spacing_sm'),
            'md' => __('mksine::page_builder.presentation_spacing_md'),
            'lg' => __('mksine::page_builder.presentation_spacing_lg'),
        ];

        return [
            Select::make('presentation.align')
                ->label(__('mksine::page_builder.presentation_align'))
                ->options([
                    'start' => __('mksine::page_builder.presentation_align_start'),
                    'right' => __('mksine::page_builder.presentation_align_right'),
                    'center' => __('mksine::page_builder.presentation_align_center'),
                    'left' => __('mksine::page_builder.presentation_align_left'),
                ])
                ->default('start')
                ->native(false),
            ColorPicker::make('presentation.text_color')
                ->label(__('mksine::page_builder.presentation_text_color'))
                ->hex()
                ->nullable(),
            ColorPicker::make('presentation.background_color')
                ->label(__('mksine::page_builder.presentation_background_color'))
                ->hex()
                ->nullable(),
            Select::make('presentation.border_width')
                ->label(__('mksine::page_builder.presentation_border_width'))
                ->options($scale)
                ->default('none')
                ->native(false),
            ColorPicker::make('presentation.border_color')
                ->label(__('mksine::page_builder.presentation_border_color'))
                ->hex()
                ->nullable(),
            Select::make('presentation.border_radius')
                ->label(__('mksine::page_builder.presentation_border_radius'))
                ->options($scale)
                ->default('none')
                ->native(false),
            Select::make('presentation.padding')
                ->label(__('mksine::page_builder.presentation_padding'))
                ->options($scale)
                ->default('none')
                ->native(false),
            Select::make('presentation.spacing')
                ->label(__('mksine::page_builder.presentation_spacing'))
                ->options($scale)
                ->default('none')
                ->native(false),
            Select::make('presentation.background')
                ->label(__('mksine::page_builder.presentation_background'))
                ->options([
                    'none' => __('mksine::page_builder.presentation_spacing_none'),
                    'muted' => __('mksine::page_builder.presentation_background_muted'),
                    'brand' => __('mksine::page_builder.presentation_background_brand'),
                ])
                ->default('none')
                ->native(false),
            Select::make('presentation.visibility')
                ->label(__('mksine::page_builder.presentation_visibility'))
                ->options([
                    'always' => __('mksine::page_builder.presentation_visibility_always'),
                    'hide_mobile' => __('mksine::page_builder.presentation_visibility_hide_mobile'),
                    'hide_desktop' => __('mksine::page_builder.presentation_visibility_hide_desktop'),
                ])
                ->default('always')
                ->native(false),
            Select::make('presentation.width')
                ->label(__('mksine::page_builder.presentation_width'))
                ->options([
                    'full' => __('mksine::page_builder.presentation_width_full'),
                    'contained' => __('mksine::page_builder.presentation_width_contained'),
                ])
                ->default('full')
                ->native(false),
            TextInput::make('presentation.anchor')
                ->label(__('mksine::page_builder.presentation_anchor'))
                ->helperText(__('mksine::page_builder.presentation_anchor_help'))
                ->maxLength(64)
                ->columnSpanFull(),
        ];
    }
}
