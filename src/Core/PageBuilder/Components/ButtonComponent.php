<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class ButtonComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'button';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_button');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-cursor-arrow-ripple';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_INTERACTIVE;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_button');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('text')
                ->label(__('mksine::page_builder.component_labels.button_text'))
                ->required()
                ->maxLength(100)
                ->placeholder(__('mksine::page_builder.component_labels.click_here')),
            TextInput::make('url')
                ->label(__('mksine::page_builder.component_labels.link_url'))
                ->required()
                ->url()
                ->placeholder('https://'),
            Select::make('style')
                ->label(__('mksine::page_builder.component_labels.style'))
                ->options([
                    'primary' => __('mksine::page_builder.component_labels.primary'),
                    'secondary' => __('mksine::page_builder.component_labels.secondary'),
                    'outline' => __('mksine::page_builder.component_labels.outline'),
                    'ghost' => __('mksine::page_builder.component_labels.ghost'),
                ])
                ->default('primary')
                ->native(false),
            Select::make('size')
                ->label(__('mksine::page_builder.component_labels.size'))
                ->options([
                    'sm' => __('mksine::page_builder.component_labels.small'),
                    'md' => __('mksine::page_builder.component_labels.medium'),
                    'lg' => __('mksine::page_builder.component_labels.large'),
                ])
                ->default('md')
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
            Toggle::make('new_tab')
                ->label(__('mksine::page_builder.component_labels.open_in_new_tab'))
                ->default(false),
            Toggle::make('full_width')
                ->label(__('mksine::page_builder.component_labels.full_width'))
                ->default(false),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'text' => '',
            'url' => '',
            'style' => 'primary',
            'size' => 'md',
            'alignment' => 'left',
            'new_tab' => false,
            'full_width' => false,
        ];
    }
}
