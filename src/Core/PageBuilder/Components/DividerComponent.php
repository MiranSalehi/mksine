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
        return __('Divider');
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
        return __('Add a horizontal line separator.');
    }

    public static function getSchema(): array
    {
        return [
            Select::make('style')
                ->label(__('Style'))
                ->options([
                    'solid' => __('Solid'),
                    'dashed' => __('Dashed'),
                    'dotted' => __('Dotted'),
                ])
                ->default('solid')
                ->native(false),
            Select::make('width')
                ->label(__('Width'))
                ->options([
                    '25' => '25%',
                    '50' => '50%',
                    '75' => '75%',
                    '100' => '100%',
                ])
                ->default('100')
                ->native(false),
            Select::make('alignment')
                ->label(__('Alignment'))
                ->options([
                    'left' => __('Left'),
                    'center' => __('Center'),
                    'right' => __('Right'),
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
