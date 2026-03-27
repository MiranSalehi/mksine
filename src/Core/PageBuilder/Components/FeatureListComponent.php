<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class FeatureListComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'features';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_features');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-list-bullet';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_CONTENT;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_features');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('heading')
                ->label(__('mksine::page_builder.component_labels.section_heading'))
                ->maxLength(255)
                ->placeholder(__('mksine::page_builder.component_labels.our_features'))
                ->columnSpanFull(),
            Textarea::make('subheading')
                ->label(__('mksine::page_builder.component_labels.section_subheading'))
                ->rows(2)
                ->maxLength(500)
                ->columnSpanFull(),
            Select::make('columns')
                ->label(__('mksine::page_builder.component_labels.columns'))
                ->options([
                    2 => __('mksine::page_builder.component_labels.two_columns'),
                    3 => __('mksine::page_builder.component_labels.three_columns'),
                    4 => __('mksine::page_builder.component_labels.four_columns'),
                ])
                ->default(3)
                ->native(false),
            Select::make('style')
                ->label(__('mksine::page_builder.component_labels.card_style'))
                ->options([
                    'simple' => __('mksine::page_builder.component_labels.simple'),
                    'bordered' => __('mksine::page_builder.component_labels.bordered'),
                    'shadowed' => __('mksine::page_builder.component_labels.shadowed'),
                    'filled' => __('mksine::page_builder.component_labels.filled'),
                ])
                ->default('simple')
                ->native(false),
            Select::make('icon_style')
                ->label(__('mksine::page_builder.component_labels.icon_style'))
                ->options([
                    'circle' => __('mksine::page_builder.component_labels.circle'),
                    'square' => __('mksine::page_builder.component_labels.square'),
                    'none' => __('mksine::page_builder.component_labels.no_background'),
                ])
                ->default('circle')
                ->native(false),
            Repeater::make('features')
                ->label(__('mksine::page_builder.component_labels.features'))
                ->schema([
                    TextInput::make('icon')
                        ->label(__('mksine::page_builder.component_labels.icon'))
                        ->placeholder('heroicon-o-star')
                        ->helperText(__('mksine::page_builder.component_labels.heroicon_help')),
                    TextInput::make('title')
                        ->label(__('mksine::page_builder.component_labels.title'))
                        ->required()
                        ->maxLength(100),
                    Textarea::make('description')
                        ->label(__('mksine::page_builder.component_labels.description'))
                        ->rows(2)
                        ->maxLength(300),
                    TextInput::make('link')
                        ->label(__('mksine::page_builder.component_labels.link_optional'))
                        ->url()
                        ->placeholder('https://'),
                ])
                ->columns(2)
                ->defaultItems(3)
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                ->columnSpanFull(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'heading' => '',
            'subheading' => '',
            'columns' => 3,
            'style' => 'simple',
            'icon_style' => 'circle',
            'features' => [
                ['icon' => 'heroicon-o-bolt', 'title' => 'Fast', 'description' => '', 'link' => ''],
                ['icon' => 'heroicon-o-shield-check', 'title' => 'Secure', 'description' => '', 'link' => ''],
                ['icon' => 'heroicon-o-heart', 'title' => 'Reliable', 'description' => '', 'link' => ''],
            ],
        ];
    }
}
