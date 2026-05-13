<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;
use Miran\Mksine\Core\PageBuilder\Support\TwelveColumnSpanNormalizer;

/**
 * 12-column CSS grid tracks: regions are repeater rows; each row sets base span (+ optional breakpoints).
 *
 * Row count replaces the retired “regions” select — adjust regions by adding or removing repeater items.
 *
 * Use {@see ColumnsComponent} for simpler equal/preset layouts.
 */
final class GridLayoutComponent extends BaseBuilderComponent
{
    /** @var list<string> */
    private static array $responsiveSpanKeys = ['span_sm', 'span_md', 'span_lg'];

    public static function getType(): string
    {
        return 'grid_layout';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_grid_layout');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-squares-2x2';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_LAYOUT;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_grid_layout');
    }

    public static function getSchema(): array
    {
        return [
            Repeater::make('column_spans')
                ->label(__('mksine::page_builder.component_labels.grid_regions_repeater_label'))
                ->helperText(__('mksine::page_builder.component_labels.grid_regions_repeater_help'))
                ->schema([
                    TextInput::make('span')
                        ->label(__('mksine::page_builder.component_labels.column_span_units'))
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(12)
                        ->default(6),
                    TextInput::make('span_sm')
                        ->label(__('mksine::page_builder.component_labels.column_span_units_sm'))
                        ->numeric()
                        ->nullable()
                        ->minValue(1)
                        ->maxValue(12)
                        ->helperText(__('mksine::page_builder.component_labels.column_span_units_sm_hint')),
                    TextInput::make('span_md')
                        ->label(__('mksine::page_builder.component_labels.column_span_units_md'))
                        ->numeric()
                        ->nullable()
                        ->minValue(1)
                        ->maxValue(12)
                        ->helperText(__('mksine::page_builder.component_labels.column_span_units_md_hint')),
                    TextInput::make('span_lg')
                        ->label(__('mksine::page_builder.component_labels.column_span_units_lg'))
                        ->numeric()
                        ->nullable()
                        ->minValue(1)
                        ->maxValue(12)
                        ->helperText(__('mksine::page_builder.component_labels.column_span_units_lg_hint')),
                ])
                ->default([
                    ['span' => 6],
                    ['span' => 6],
                ])
                ->minItems(TwelveColumnSpanNormalizer::MIN_COLUMNS)
                ->maxItems(TwelveColumnSpanNormalizer::MAX_COLUMNS)
                ->reorderable()
                ->collapsible()
                ->columnSpanFull(),
            Select::make('gap')
                ->label(__('mksine::page_builder.component_labels.gap_between_columns'))
                ->options([
                    'none' => __('mksine::page_builder.component_labels.none'),
                    'sm' => __('mksine::page_builder.component_labels.small'),
                    'md' => __('mksine::page_builder.component_labels.medium'),
                    'lg' => __('mksine::page_builder.component_labels.large'),
                ])
                ->default('md')
                ->native(false),
            Select::make('vertical_alignment')
                ->label(__('mksine::page_builder.component_labels.vertical_alignment'))
                ->options([
                    'start' => __('mksine::page_builder.component_labels.top'),
                    'center' => __('mksine::page_builder.component_labels.center'),
                    'end' => __('mksine::page_builder.component_labels.bottom'),
                    'stretch' => __('mksine::page_builder.component_labels.stretch'),
                ])
                ->default('start')
                ->native(false),
            Select::make('stack_on_mobile')
                ->label(__('mksine::page_builder.component_labels.stack_on_mobile'))
                ->options([
                    'always' => __('mksine::page_builder.component_labels.always_stack'),
                    'tablet' => __('mksine::page_builder.component_labels.stack_tablet_mobile'),
                    'mobile' => __('mksine::page_builder.component_labels.stack_mobile_only'),
                    'never' => __('mksine::page_builder.component_labels.never_stack'),
                ])
                ->default('mobile')
                ->native(false),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'column_spans' => [
                ['span' => 6],
                ['span' => 6],
            ],
            'gap' => 'md',
            'vertical_alignment' => 'start',
            'stack_on_mobile' => 'mobile',
        ];
    }

    public static function validate(array $data): array
    {
        $rawSpans = array_values((array) ($data['column_spans'] ?? []));

        while (count($rawSpans) < TwelveColumnSpanNormalizer::MIN_COLUMNS) {
            $rawSpans[] = ['span' => 6];
        }

        $columns = max(
            TwelveColumnSpanNormalizer::MIN_COLUMNS,
            min(TwelveColumnSpanNormalizer::MAX_COLUMNS, count($rawSpans)),
        );

        $baseNormalized = TwelveColumnSpanNormalizer::normalizeRepeaterSpans($rawSpans, $columns);

        $merged = [];
        foreach ($baseNormalized as $i => $norm) {
            $merged[] = self::mergeResponsiveSpanRow($rawSpans[$i] ?? null, $norm['span']);
        }

        $data['columns'] = $columns;
        $data['column_spans'] = $merged;

        return $data;
    }

    /**
     * @param  array<string, mixed>|null  $original
     * @return array{span: int, span_sm?: int, span_md?: int, span_lg?: int}
     */
    private static function mergeResponsiveSpanRow(?array $original, int $normalizedSpan): array
    {
        $original ??= [];
        $row = ['span' => $normalizedSpan];

        foreach (self::$responsiveSpanKeys as $key) {
            if (! array_key_exists($key, $original)) {
                continue;
            }
            $v = $original[$key];
            if ($v === null || $v === '') {
                continue;
            }
            $n = (int) $v;
            if ($n >= 1 && $n <= 12) {
                $row[$key] = $n;
            }
        }

        return $row;
    }

    public static function supportsChildren(): bool
    {
        return true;
    }

    public static function getMaxChildren(): ?int
    {
        return TwelveColumnSpanNormalizer::MAX_COLUMNS;
    }

    public static function createInstance(?string $id = null): array
    {
        $instance = parent::createInstance($id);
        $instance['data'] = self::validate(array_merge(static::getDefaultData(), is_array($instance['data'] ?? null) ? $instance['data'] : []));
        $columnCount = (int) $instance['data']['columns'];

        $instance['children'] = [];
        for ($i = 0; $i < $columnCount; $i++) {
            $instance['children'][] = [
                'id' => uniqid('col_'),
                'items' => [],
            ];
        }

        return $instance;
    }
}
