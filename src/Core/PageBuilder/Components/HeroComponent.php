<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Miran\Mksine\Filament\Forms\Components\MediaPicker;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class HeroComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'hero';
    }

    public static function getName(): string
    {
        return __('Hero Section');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-window';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_LAYOUT;
    }

    public static function getDescription(): string
    {
        return __('A full-width hero section with title, subtitle, and call-to-action.');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('title')
                ->label(__('Title'))
                ->required()
                ->maxLength(255)
                ->placeholder(__('Welcome to our site'))
                ->columnSpanFull(),
            Textarea::make('subtitle')
                ->label(__('Subtitle'))
                ->rows(2)
                ->maxLength(500)
                ->placeholder(__('A brief description...'))
                ->columnSpanFull(),
            MediaPicker::make('background_image')
                ->label(__('Background Image'))
                ->isRelation(false)
                ->collection('page_builder')
                ->acceptedFileTypes(['image/*'])
                ->columnSpanFull(),
            Select::make('overlay')
                ->label(__('Overlay'))
                ->options([
                    'none' => __('None'),
                    'light' => __('Light'),
                    'dark' => __('Dark'),
                    'gradient' => __('Gradient'),
                ])
                ->default('dark')
                ->native(false),
            Select::make('height')
                ->label(__('Height'))
                ->options([
                    'small' => __('Small (300px)'),
                    'medium' => __('Medium (450px)'),
                    'large' => __('Large (600px)'),
                    'full' => __('Full Screen'),
                ])
                ->default('medium')
                ->native(false),
            Select::make('text_alignment')
                ->label(__('Text Alignment'))
                ->options([
                    'left' => __('Left'),
                    'center' => __('Center'),
                    'right' => __('Right'),
                ])
                ->default('center')
                ->native(false),
            Select::make('text_color')
                ->label(__('Text Color'))
                ->options([
                    'white' => __('White'),
                    'dark' => __('Dark'),
                ])
                ->default('white')
                ->native(false),
            TextInput::make('button_text')
                ->label(__('Button Text'))
                ->maxLength(100)
                ->placeholder(__('Get Started')),
            TextInput::make('button_url')
                ->label(__('Button URL'))
                ->url()
                ->placeholder('https://'),
            Select::make('button_style')
                ->label(__('Button Style'))
                ->options([
                    'primary' => __('Primary'),
                    'secondary' => __('Secondary'),
                    'outline' => __('Outline'),
                ])
                ->default('primary')
                ->native(false),
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
            'subtitle' => '',
            'background_image' => null,
            'overlay' => 'dark',
            'height' => 'medium',
            'text_alignment' => 'center',
            'text_color' => 'white',
            'button_text' => '',
            'button_url' => '',
            'button_style' => 'primary',
            'show_secondary_button' => false,
            'secondary_button_text' => '',
            'secondary_button_url' => '',
        ];
    }
}
