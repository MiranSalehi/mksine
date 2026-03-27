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
        return __('mksine::page_builder.component_labels.name_slider');
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
        return __('mksine::page_builder.component_labels.desc_slider');
    }

    public static function getSchema(): array
    {
        return [
            Select::make('type')
                ->label(__('mksine::page_builder.component_labels.slider_type'))
                ->options([
                    'image' => __('mksine::page_builder.component_labels.image_slider'),
                    'content' => __('mksine::page_builder.component_labels.content_slider'),
                ])
                ->default('image')
                ->native(false)
                ->live(),
            Select::make('height')
                ->label(__('mksine::page_builder.component_labels.height'))
                ->options([
                    'small' => __('mksine::page_builder.component_labels.small_250'),
                    'medium' => __('mksine::page_builder.component_labels.medium_400'),
                    'large' => __('mksine::page_builder.component_labels.large_550'),
                    'auto' => __('mksine::page_builder.component_labels.auto'),
                ])
                ->default('medium')
                ->native(false),
            Toggle::make('autoplay')
                ->label(__('mksine::page_builder.component_labels.autoplay'))
                ->default(true),
            TextInput::make('autoplay_speed')
                ->label(__('mksine::page_builder.component_labels.autoplay_speed'))
                ->numeric()
                ->default(5000)
                ->visible(fn ($get) => $get('autoplay')),
            Toggle::make('show_arrows')
                ->label(__('mksine::page_builder.component_labels.show_navigation_arrows'))
                ->default(true),
            Toggle::make('show_dots')
                ->label(__('mksine::page_builder.component_labels.show_dots_indicators'))
                ->default(true),
            Toggle::make('loop')
                ->label(__('mksine::page_builder.component_labels.infinite_loop'))
                ->default(true),
            Select::make('effect')
                ->label(__('mksine::page_builder.component_labels.transition_effect'))
                ->options([
                    'slide' => __('mksine::page_builder.component_labels.slide'),
                    'fade' => __('mksine::page_builder.component_labels.fade'),
                ])
                ->default('slide')
                ->native(false),
            Repeater::make('slides')
                ->label(__('mksine::page_builder.component_labels.slides'))
                ->schema([
                    MediaPicker::make('image')
                        ->label(__('mksine::page_builder.component_labels.field_image'))
                        ->required()
                        ->isRelation(false)
                        ->collection('page_builder')
                        ->acceptedFileTypes(['image/*'])
                        ->columnSpanFull(),
                    TextInput::make('title')
                        ->label(__('mksine::page_builder.component_labels.title_optional'))
                        ->maxLength(255),
                    TextInput::make('subtitle')
                        ->label(__('mksine::page_builder.component_labels.subtitle_optional'))
                        ->maxLength(500),
                    TextInput::make('button_text')
                        ->label(__('mksine::page_builder.component_labels.button_text')),
                    TextInput::make('button_url')
                        ->label(__('mksine::page_builder.component_labels.button_url'))
                        ->url(),
                    TextInput::make('alt')
                        ->label(__('mksine::page_builder.component_labels.alt_text'))
                        ->maxLength(255),
                ])
                ->columns(2)
                ->defaultItems(3)
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? __('mksine::page_builder.component_labels.slide_label'))
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
