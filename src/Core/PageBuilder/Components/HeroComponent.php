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
        return __('mksine::page_builder.component_labels.name_hero');
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
        return __('mksine::page_builder.component_labels.desc_hero');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('title')
                ->label(__('mksine::page_builder.component_labels.title'))
                ->required()
                ->maxLength(255)
                ->placeholder(__('mksine::page_builder.component_labels.welcome_site'))
                ->columnSpanFull(),
            Textarea::make('subtitle')
                ->label(__('mksine::page_builder.component_labels.subtitle'))
                ->rows(2)
                ->maxLength(500)
                ->placeholder(__('mksine::page_builder.component_labels.brief_description'))
                ->columnSpanFull(),
            MediaPicker::make('background_image')
                ->label(__('mksine::page_builder.component_labels.background_image'))
                ->isRelation(false)
                ->collection('page_builder')
                ->acceptedFileTypes(['image/*'])
                ->columnSpanFull(),
            Select::make('overlay')
                ->label(__('mksine::page_builder.component_labels.overlay'))
                ->options([
                    'none' => __('mksine::page_builder.component_labels.none'),
                    'light' => __('mksine::page_builder.component_labels.light'),
                    'dark' => __('mksine::page_builder.component_labels.dark'),
                    'gradient' => __('mksine::page_builder.component_labels.gradient'),
                ])
                ->default('dark')
                ->native(false),
            Select::make('height')
                ->label(__('mksine::page_builder.component_labels.height'))
                ->options([
                    'small' => __('mksine::page_builder.component_labels.small_300'),
                    'medium' => __('mksine::page_builder.component_labels.medium_450'),
                    'large' => __('mksine::page_builder.component_labels.large_600'),
                    'full' => __('mksine::page_builder.component_labels.full_screen'),
                ])
                ->default('medium')
                ->native(false),
            Select::make('text_alignment')
                ->label(__('mksine::page_builder.component_labels.text_alignment'))
                ->options([
                    'left' => __('mksine::page_builder.component_labels.left'),
                    'center' => __('mksine::page_builder.component_labels.center'),
                    'right' => __('mksine::page_builder.component_labels.right'),
                ])
                ->default('center')
                ->native(false),
            Select::make('text_color')
                ->label(__('mksine::page_builder.component_labels.text_color'))
                ->options([
                    'white' => __('mksine::page_builder.component_labels.white'),
                    'dark' => __('mksine::page_builder.component_labels.dark'),
                ])
                ->default('white')
                ->native(false),
            TextInput::make('button_text')
                ->label(__('mksine::page_builder.component_labels.button_text'))
                ->maxLength(100)
                ->placeholder(__('mksine::page_builder.component_labels.get_started')),
            TextInput::make('button_url')
                ->label(__('mksine::page_builder.component_labels.button_url'))
                ->url()
                ->placeholder('https://'),
            Select::make('button_style')
                ->label(__('mksine::page_builder.component_labels.style'))
                ->options([
                    'primary' => __('mksine::page_builder.component_labels.primary'),
                    'secondary' => __('mksine::page_builder.component_labels.secondary'),
                    'outline' => __('mksine::page_builder.component_labels.outline'),
                ])
                ->default('primary')
                ->native(false),
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
