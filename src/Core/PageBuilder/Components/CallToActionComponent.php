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
        return __('Call to Action');
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
        return __('A section to encourage user action with title, text, and buttons.');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('title')
                ->label(__('Title'))
                ->required()
                ->maxLength(255)
                ->placeholder(__('Ready to get started?'))
                ->columnSpanFull(),
            Textarea::make('description')
                ->label(__('Description'))
                ->rows(2)
                ->maxLength(500)
                ->placeholder(__('Join thousands of satisfied customers...'))
                ->columnSpanFull(),
            Select::make('style')
                ->label(__('Style'))
                ->options([
                    'simple' => __('Simple'),
                    'boxed' => __('Boxed'),
                    'gradient' => __('Gradient'),
                    'dark' => __('Dark'),
                ])
                ->default('gradient')
                ->native(false),
            Select::make('alignment')
                ->label(__('Alignment'))
                ->options([
                    'left' => __('Left'),
                    'center' => __('Center'),
                    'between' => __('Space Between'),
                ])
                ->default('center')
                ->native(false),
            TextInput::make('button_text')
                ->label(__('Button Text'))
                ->required()
                ->maxLength(100)
                ->placeholder(__('Get Started')),
            TextInput::make('button_url')
                ->label(__('Button URL'))
                ->required()
                ->url()
                ->placeholder('https://'),
            Toggle::make('show_secondary_button')
                ->label(__('Show Secondary Button'))
                ->default(false)
                ->live(),
            TextInput::make('secondary_button_text')
                ->label(__('Secondary Button Text'))
                ->maxLength(100)
                ->visible(fn ($get) => $get('show_secondary_button')),
            TextInput::make('secondary_button_url')
                ->label(__('Secondary Button URL'))
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
