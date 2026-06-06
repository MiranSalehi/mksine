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
        return __('mksine::page_builder.component_labels.name_spacer');
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
        return __('mksine::page_builder.component_labels.desc_spacer');
    }

    public static function getSchema(): array
    {
        return [
            Select::make('size')
                ->label(__('mksine::page_builder.component_labels.spacing_size'))
                ->options([
                    'xs' => __('mksine::page_builder.component_labels.extra_small_8'),
                    'sm' => __('mksine::page_builder.component_labels.small_16'),
                    'md' => __('mksine::page_builder.component_labels.medium_32'),
                    'lg' => __('mksine::page_builder.component_labels.large_48'),
                    'xl' => __('mksine::page_builder.component_labels.extra_large_64'),
                    '2xl' => __('mksine::page_builder.component_labels.two_x_large_96'),
                ])
                ->default('md')
                ->required()
                ->native(false)
                ->position('bottom'),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'size' => 'md',
        ];
    }
}
