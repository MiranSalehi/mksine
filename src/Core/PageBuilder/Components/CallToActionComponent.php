<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class CallToActionComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'cta';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_cta');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-megaphone';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_INTERACTIVE;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_cta');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('title')
                ->label(__('mksine::page_builder.component_labels.title'))
                ->required()
                ->maxLength(255)
                ->placeholder(__('mksine::page_builder.component_labels.ready_to_get_started'))
                ->columnSpanFull(),
            Textarea::make('description')
                ->label(__('mksine::page_builder.component_labels.description'))
                ->rows(2)
                ->maxLength(500)
                ->placeholder(__('mksine::page_builder.component_labels.join_thousands'))
                ->columnSpanFull(),
            Select::make('style')
                ->label(__('mksine::page_builder.component_labels.style'))
                ->options([
                    'simple' => __('mksine::page_builder.component_labels.simple'),
                    'boxed' => __('mksine::page_builder.component_labels.boxed'),
                    'gradient' => __('mksine::page_builder.component_labels.gradient'),
                    'dark' => __('mksine::page_builder.component_labels.dark'),
                ])
                ->default('gradient')
                ->native(false),
            Select::make('alignment')
                ->label(__('mksine::page_builder.component_labels.alignment'))
                ->options([
                    'left' => __('mksine::page_builder.component_labels.left'),
                    'center' => __('mksine::page_builder.component_labels.center'),
                    'between' => __('mksine::page_builder.component_labels.space_between'),
                ])
                ->default('center')
                ->native(false),
            TextInput::make('button_text')
                ->label(__('mksine::page_builder.component_labels.button_text'))
                ->required()
                ->maxLength(100)
                ->placeholder(__('mksine::page_builder.component_labels.get_started')),
            TextInput::make('button_url')
                ->label(__('mksine::page_builder.component_labels.button_url'))
                ->required()
                ->url()
                ->placeholder('https://'),
            Toggle::make('show_secondary_button')
                ->label(__('mksine::page_builder.component_labels.show_secondary_button'))
                ->default(false)
                ->live(),
            TextInput::make('secondary_button_text')
                ->label(__('mksine::page_builder.component_labels.secondary_button_text'))
                ->maxLength(100)
                ->visible(fn ($get) => $get('show_secondary_button')),
            TextInput::make('secondary_button_url')
                ->label(__('mksine::page_builder.component_labels.secondary_button_url'))
                ->url()
                ->visible(fn ($get) => $get('show_secondary_button')),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'title' => '',
            'description' => '',
            'style' => 'gradient',
            'alignment' => 'center',
            'button_text' => '',
            'button_url' => '',
            'show_secondary_button' => false,
            'secondary_button_text' => '',
            'secondary_button_url' => '',
        ];
    }
}
