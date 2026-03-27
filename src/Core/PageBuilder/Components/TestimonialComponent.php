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
        return __('mksine::page_builder.component_labels.name_testimonial');
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
        return __('mksine::page_builder.component_labels.desc_testimonial');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('heading')
                ->label(__('mksine::page_builder.component_labels.section_heading'))
                ->maxLength(255)
                ->placeholder(__('mksine::page_builder.component_labels.what_customers_say'))
                ->columnSpanFull(),
            Select::make('layout')
                ->label(__('mksine::page_builder.component_labels.layout'))
                ->options([
                    'grid' => __('mksine::page_builder.component_labels.grid'),
                    'carousel' => __('mksine::page_builder.component_labels.carousel'),
                    'single' => __('mksine::page_builder.component_labels.single_featured'),
                ])
                ->default('grid')
                ->native(false),
            Select::make('columns')
                ->label(__('mksine::page_builder.component_labels.columns'))
                ->options([
                    1 => __('mksine::page_builder.component_labels.one_column'),
                    2 => __('mksine::page_builder.component_labels.two_columns'),
                    3 => __('mksine::page_builder.component_labels.three_columns'),
                ])
                ->default(3)
                ->native(false)
                ->visible(fn ($get) => $get('layout') === 'grid'),
            Select::make('style')
                ->label(__('mksine::page_builder.component_labels.card_style'))
                ->options([
                    'simple' => __('mksine::page_builder.component_labels.simple'),
                    'bordered' => __('mksine::page_builder.component_labels.bordered'),
                    'shadowed' => __('mksine::page_builder.component_labels.shadowed'),
                    'quote' => __('mksine::page_builder.component_labels.quote_style'),
                ])
                ->default('shadowed')
                ->native(false),
            Repeater::make('testimonials')
                ->label(__('mksine::page_builder.component_labels.testimonials'))
                ->schema([
                    Textarea::make('content')
                        ->label(__('mksine::page_builder.component_labels.testimonial_text'))
                        ->required()
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                    TextInput::make('author_name')
                        ->label(__('mksine::page_builder.component_labels.author_name'))
                        ->required()
                        ->maxLength(100),
                    TextInput::make('author_title')
                        ->label(__('mksine::page_builder.component_labels.author_title_company'))
                        ->maxLength(100)
                        ->placeholder(__('mksine::page_builder.component_labels.ceo_company')),
                    MediaPicker::make('author_image')
                        ->label(__('mksine::page_builder.component_labels.author_image'))
                        ->isRelation(false)
                        ->collection('page_builder')
                        ->acceptedFileTypes(['image/*']),
                    Select::make('rating')
                        ->label(__('mksine::page_builder.component_labels.rating'))
                        ->options([
                            0 => __('mksine::page_builder.component_labels.no_rating'),
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
