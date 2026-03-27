<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Select;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class ColumnsComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'columns';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_columns');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-view-columns';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_LAYOUT;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_columns');
    }

    public static function getSchema(): array
    {
        return [
            Select::make('columns')
                ->label(__('mksine::page_builder.component_labels.number_of_columns'))
                ->options([
                    2 => __('mksine::page_builder.component_labels.two_columns'),
                    3 => __('mksine::page_builder.component_labels.three_columns'),
                    4 => __('mksine::page_builder.component_labels.four_columns'),
                ])
                ->default(2)
                ->required()
                ->native(false)
                ->live(),
            Select::make('layout')
                ->label(__('mksine::page_builder.component_labels.column_layout'))
                ->options(fn ($get) => static::getLayoutOptions((int) ($get('columns') ?? 2)))
                ->default('equal')
                ->native(false),
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
            'columns' => 2,
            'layout' => 'equal',
            'gap' => 'md',
            'vertical_alignment' => 'start',
            'stack_on_mobile' => 'mobile',
        ];
    }

    public static function supportsChildren(): bool
    {
        return true;
    }

    public static function getMaxChildren(): ?int
    {
        return 4; // Max 4 columns
    }

    /**
     * Get layout options based on number of columns.
     */
    protected static function getLayoutOptions(int $columns): array
    {
        $options = [
            'equal' => __('mksine::page_builder.component_labels.equal_width'),
        ];

        if ($columns === 2) {
            $options['1-2'] = __('mksine::page_builder.component_labels.layout_1_2');
            $options['2-1'] = __('mksine::page_builder.component_labels.layout_2_1');
            $options['1-3'] = __('mksine::page_builder.component_labels.layout_1_3');
            $options['3-1'] = __('mksine::page_builder.component_labels.layout_3_1');
        }

        if ($columns === 3) {
            $options['1-2-1'] = __('mksine::page_builder.component_labels.layout_1_2_1');
            $options['2-1-1'] = __('mksine::page_builder.component_labels.layout_2_1_1');
            $options['1-1-2'] = __('mksine::page_builder.component_labels.layout_1_1_2');
        }

        return $options;
    }

    /**
     * Create instance with empty columns based on column count.
     */
    public static function createInstance(?string $id = null): array
    {
        $instance = parent::createInstance($id);
        $columnCount = $instance['data']['columns'] ?? 2;

        // Initialize empty children for each column
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
