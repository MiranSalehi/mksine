<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Select;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class SpacerComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'spacer';
    }

    public static function getName(): string
    {
        return __('Spacer');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-arrows-up-down';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_LAYOUT;
    }

    public static function getDescription(): string
    {
        return __('Add vertical spacing between elements.');
    }

    public static function getSchema(): array
    {
        return [
            Select::make('size')
                ->label(__('Spacing Size'))
                ->options([
                    'xs' => __('Extra Small (8px)'),
                    'sm' => __('Small (16px)'),
                    'md' => __('Medium (32px)'),
                    'lg' => __('Large (48px)'),
                    'xl' => __('Extra Large (64px)'),
                    '2xl' => __('2X Large (96px)'),
                ])
                ->default('md')
                ->required()
                ->native(false),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'size' => 'md',
        ];
    }
}
