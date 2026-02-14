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
        return __('Columns');
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
        return __('Create multi-column layouts. Drag components into each column.');
    }

    public static function getSchema(): array
    {
        return [
            Select::make('columns')
                ->label(__('Number of Columns'))
                ->options([
                    2 => __('2 Columns'),
                    3 => __('3 Columns'),
                    4 => __('4 Columns'),
                ])
                ->default(2)
                ->required()
                ->native(false)
                ->live(),
            Select::make('layout')
                ->label(__('Column Layout'))
                ->options(fn ($get) => static::getLayoutOptions((int) ($get('columns') ?? 2)))
                ->default('equal')
                ->native(false),
            Select::make('gap')
                ->label(__('Gap Between Columns'))
                ->options([
                    'none' => __('None'),
                    'sm' => __('Small'),
                    'md' => __('Medium'),
                    'lg' => __('Large'),
                ])
                ->default('md')
                ->native(false),
            Select::make('vertical_alignment')
                ->label(__('Vertical Alignment'))
                ->options([
                    'start' => __('Top'),
                    'center' => __('Center'),
                    'end' => __('Bottom'),
                    'stretch' => __('Stretch'),
                ])
                ->default('start')
                ->native(false),
            Select::make('stack_on_mobile')
                ->label(__('Stack on Mobile'))
                ->options([
                    'always' => __('Always Stack'),
                    'tablet' => __('Stack on Tablet & Mobile'),
                    'mobile' => __('Stack on Mobile Only'),
                    'never' => __('Never Stack'),
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
            'equal' => __('Equal Width'),
        ];

        if ($columns === 2) {
            $options['1-2'] = '1/3 + 2/3';
            $options['2-1'] = '2/3 + 1/3';
            $options['1-3'] = '1/4 + 3/4';
            $options['3-1'] = '3/4 + 1/4';
        }

        if ($columns === 3) {
            $options['1-2-1'] = '1/4 + 1/2 + 1/4';
            $options['2-1-1'] = '1/2 + 1/4 + 1/4';
            $options['1-1-2'] = '1/4 + 1/4 + 1/2';
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
