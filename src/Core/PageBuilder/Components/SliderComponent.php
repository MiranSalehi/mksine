<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Miran\Mksine\Filament\Forms\Components\MediaPicker;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class SliderComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'slider';
    }

    public static function getName(): string
    {
        return __('Slider/Carousel');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-photo';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_MEDIA;
    }

    public static function getDescription(): string
    {
        return __('An image or content carousel with navigation.');
    }

    public static function getSchema(): array
    {
        return [
            Select::make('type')
                ->label(__('Slider Type'))
                ->options([
                    'image' => __('Image Slider'),
                    'content' => __('Content Slider'),
                ])
                ->default('image')
                ->native(false)
                ->live(),
            Select::make('height')
                ->label(__('Height'))
                ->options([
                    'small' => __('Small (250px)'),
                    'medium' => __('Medium (400px)'),
                    'large' => __('Large (550px)'),
                    'auto' => __('Auto'),
                ])
                ->default('medium')
                ->native(false),
            Toggle::make('autoplay')
                ->label(__('Autoplay'))
                ->default(true),
            TextInput::make('autoplay_speed')
                ->label(__('Autoplay Speed (ms)'))
                ->numeric()
                ->default(5000)
                ->visible(fn ($get) => $get('autoplay')),
            Toggle::make('show_arrows')
                ->label(__('Show Navigation Arrows'))
                ->default(true),
            Toggle::make('show_dots')
                ->label(__('Show Dots/Indicators'))
                ->default(true),
            Toggle::make('loop')
                ->label(__('Infinite Loop'))
                ->default(true),
            Select::make('effect')
                ->label(__('Transition Effect'))
                ->options([
                    'slide' => __('Slide'),
                    'fade' => __('Fade'),
                ])
                ->default('slide')
                ->native(false),
            Repeater::make('slides')
                ->label(__('Slides'))
                ->schema([
                    MediaPicker::make('image')
                        ->label(__('Image'))
                        ->required()
                        ->isRelation(false)
                        ->collection('page_builder')
                        ->acceptedFileTypes(['image/*'])
                        ->columnSpanFull(),
                    TextInput::make('title')
                        ->label(__('Title (optional)'))
                        ->maxLength(255),
                    TextInput::make('subtitle')
                        ->label(__('Subtitle (optional)'))
                        ->maxLength(500),
                    TextInput::make('button_text')
                        ->label(__('Button Text')),
                    TextInput::make('button_url')
                        ->label(__('Button URL'))
                        ->url(),
                    TextInput::make('alt')
                        ->label(__('Alt Text'))
                        ->maxLength(255),
                ])
                ->columns(2)
                ->defaultItems(3)
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? __('Slide'))
                ->columnSpanFull(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'type' => 'image',
            'height' => 'medium',
            'autoplay' => true,
            'autoplay_speed' => 5000,
            'show_arrows' => true,
            'show_dots' => true,
            'loop' => true,
            'effect' => 'slide',
            'slides' => [],
        ];
    }
}
