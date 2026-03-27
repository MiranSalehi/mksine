<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class HeadingComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'heading';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_heading');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-h1';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_CONTENT;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_heading');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('text')
                ->label(__('mksine::page_builder.component_labels.heading_text'))
                ->required()
                ->maxLength(255)
                ->placeholder(__('mksine::page_builder.component_labels.enter_heading_text')),
            Select::make('level')
                ->label(__('mksine::page_builder.component_labels.heading_level'))
                ->options([
                    'h1' => __('mksine::page_builder.component_labels.h1'),
                    'h2' => __('mksine::page_builder.component_labels.h2'),
                    'h3' => __('mksine::page_builder.component_labels.h3'),
                    'h4' => __('mksine::page_builder.component_labels.h4'),
                    'h5' => __('mksine::page_builder.component_labels.h5'),
                    'h6' => __('mksine::page_builder.component_labels.h6'),
                ])
                ->default('h2')
                ->required()
                ->native(false),
            Select::make('alignment')
                ->label(__('mksine::page_builder.component_labels.alignment'))
                ->options([
                    'left' => __('mksine::page_builder.component_labels.left'),
                    'center' => __('mksine::page_builder.component_labels.center'),
                    'right' => __('mksine::page_builder.component_labels.right'),
                ])
                ->default('left')
                ->native(false),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'text' => '',
            'level' => 'h2',
            'alignment' => 'left',
        ];
    }
}
