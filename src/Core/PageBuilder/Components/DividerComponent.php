<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Select;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class DividerComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'divider';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_divider');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-minus';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_LAYOUT;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_divider');
    }

    public static function getSchema(): array
    {
        return [
            Select::make('style')
                ->label(__('mksine::page_builder.component_labels.style'))
                ->options([
                    'solid' => __('mksine::page_builder.component_labels.solid'),
                    'dashed' => __('mksine::page_builder.component_labels.dashed'),
                    'dotted' => __('mksine::page_builder.component_labels.dotted'),
                ])
                ->default('solid')
                ->native(false),
            Select::make('width')
                ->label(__('mksine::page_builder.component_labels.width'))
                ->options([
                    '25' => __('mksine::page_builder.component_labels.width_25'),
                    '50' => __('mksine::page_builder.component_labels.width_50'),
                    '75' => __('mksine::page_builder.component_labels.width_75'),
                    '100' => __('mksine::page_builder.component_labels.width_100'),
                ])
                ->default('100')
                ->native(false),
            Select::make('alignment')
                ->label(__('mksine::page_builder.component_labels.alignment'))
                ->options([
                    'left' => __('mksine::page_builder.component_labels.left'),
                    'center' => __('mksine::page_builder.component_labels.center'),
                    'right' => __('mksine::page_builder.component_labels.right'),
                ])
                ->default('center')
                ->native(false),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'style' => 'solid',
            'width' => '100',
            'alignment' => 'center',
        ];
    }
}
