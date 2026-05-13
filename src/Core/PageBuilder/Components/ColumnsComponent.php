<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Select;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

/**
 * Simple equal or preset-ratio columns for editors who want stability and predictable drag targets.
 * For arbitrary widths on a 12-column grid use {@see GridLayoutComponent}.
 */
class ColumnsComponent extends BaseBuilderComponent
{
    public const MIN_COLUMNS = 2;

    public const MAX_COLUMNS = 12;

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
                ->options(static::columnCountSelectOptions())
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

    public static function validate(array $data): array
    {
        $columns = max(self::MIN_COLUMNS, min(self::MAX_COLUMNS, (int) ($data['columns'] ?? self::MIN_COLUMNS)));
        $data['columns'] = $columns;
        unset($data['width_mode'], $data['column_spans']);

        return $data;
    }

    public static function supportsChildren(): bool
    {
        return true;
    }

    public static function getMaxChildren(): ?int
    {
        return self::MAX_COLUMNS;
    }

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
     * @return array<int, string>
     */
    protected static function columnCountSelectOptions(): array
    {
        $options = [];
        for ($i = self::MIN_COLUMNS; $i <= self::MAX_COLUMNS; $i++) {
            $options[$i] = __('mksine::page_builder.component_labels.n_columns', ['count' => $i]);
        }

        return $options;
    }

    public static function createInstance(?string $id = null): array
    {
        $instance = parent::createInstance($id);
        $columnCount = (int) ($instance['data']['columns'] ?? self::MIN_COLUMNS);
        $columnCount = max(self::MIN_COLUMNS, min(self::MAX_COLUMNS, $columnCount));
        $instance['data']['columns'] = $columnCount;
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
