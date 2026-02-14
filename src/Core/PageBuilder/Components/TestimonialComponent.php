<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Miran\Mksine\Filament\Forms\Components\MediaPicker;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class TestimonialComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'testimonial';
    }

    public static function getName(): string
    {
        return __('Testimonial');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-chat-bubble-bottom-center-text';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_CONTENT;
    }

    public static function getDescription(): string
    {
        return __('Display customer testimonials and reviews.');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('heading')
                ->label(__('Section Heading'))
                ->maxLength(255)
                ->placeholder(__('What our customers say'))
                ->columnSpanFull(),
            Select::make('layout')
                ->label(__('Layout'))
                ->options([
                    'grid' => __('Grid'),
                    'carousel' => __('Carousel'),
                    'single' => __('Single Featured'),
                ])
                ->default('grid')
                ->native(false),
            Select::make('columns')
                ->label(__('Columns'))
                ->options([
                    1 => __('1 Column'),
                    2 => __('2 Columns'),
                    3 => __('3 Columns'),
                ])
                ->default(3)
                ->native(false)
                ->visible(fn ($get) => $get('layout') === 'grid'),
            Select::make('style')
                ->label(__('Card Style'))
                ->options([
                    'simple' => __('Simple'),
                    'bordered' => __('Bordered'),
                    'shadowed' => __('Shadowed'),
                    'quote' => __('Quote Style'),
                ])
                ->default('shadowed')
                ->native(false),
            Repeater::make('testimonials')
                ->label(__('Testimonials'))
                ->schema([
                    Textarea::make('content')
                        ->label(__('Testimonial Text'))
                        ->required()
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    TextInput::make('author_name')
                        ->label(__('Author Name'))
                        ->required()
                        ->maxLength(100),
                    TextInput::make('author_title')
                        ->label(__('Author Title/Company'))
                        ->maxLength(100)
                        ->placeholder(__('CEO at Company')),
                    MediaPicker::make('author_image')
                        ->label(__('Author Image'))
                        ->isRelation(false)
                        ->collection('page_builder')
                        ->acceptedFileTypes(['image/*']),
                    Select::make('rating')
                        ->label(__('Rating'))
                        ->options([
                            0 => __('No Rating'),
                            1 => '⭐',
                            2 => '⭐⭐',
                            3 => '⭐⭐⭐',
                            4 => '⭐⭐⭐⭐',
                            5 => '⭐⭐⭐⭐⭐',
                        ])
                        ->default(5)
                        ->native(false),
                ])
                ->columns(2)
                ->defaultItems(3)
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['author_name'] ?? null)
                ->columnSpanFull(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'heading' => '',
            'layout' => 'grid',
            'columns' => 3,
            'style' => 'shadowed',
            'testimonials' => [],
        ];
    }
}
