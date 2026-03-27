<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;
use Miran\Mksine\Filament\Forms\Components\CKEditor;

class TabsComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'tabs';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_tabs');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-rectangle-stack';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_LAYOUT;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_tabs');
    }

    public static function getSchema(): array
    {
        return [
            Select::make('style')
                ->label(__('mksine::page_builder.component_labels.tab_style'))
                ->options([
                    'underline' => __('mksine::page_builder.component_labels.underline'),
                    'pills' => __('mksine::page_builder.component_labels.pills'),
                    'boxed' => __('mksine::page_builder.component_labels.boxed'),
                    'buttons' => __('mksine::page_builder.component_labels.buttons'),
                ])
                ->default('underline')
                ->native(false),
            Select::make('alignment')
                ->label(__('mksine::page_builder.component_labels.tab_alignment'))
                ->options([
                    'left' => __('mksine::page_builder.component_labels.left'),
                    'center' => __('mksine::page_builder.component_labels.center'),
                    'right' => __('mksine::page_builder.component_labels.right'),
                    'full' => __('mksine::page_builder.component_labels.full_width'),
                ])
                ->default('left')
                ->native(false),
            Select::make('orientation')
                ->label(__('mksine::page_builder.component_labels.orientation'))
                ->options([
                    'horizontal' => __('mksine::page_builder.component_labels.horizontal'),
                    'vertical' => __('mksine::page_builder.component_labels.vertical'),
                ])
                ->default('horizontal')
                ->native(false),
            Repeater::make('tabs')
                ->label(__('mksine::page_builder.component_labels.tabs'))
                ->schema([
                    TextInput::make('title')
                        ->label(__('mksine::page_builder.component_labels.tab_title'))
                        ->required()
                        ->maxLength(100),
                    TextInput::make('icon')
                        ->label(__('mksine::page_builder.component_labels.icon_optional'))
                        ->placeholder('heroicon-o-home')
                        ->helperText(__('mksine::page_builder.component_labels.heroicon_name')),
                    CKEditor::make('content')
                        ->label(__('mksine::page_builder.component_labels.tab_content'))
                        ->required()
                        ->columnSpanFull(),
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
            'style' => 'underline',
            'alignment' => 'left',
            'orientation' => 'horizontal',
            'tabs' => [
                ['title' => 'Tab 1', 'icon' => '', 'content' => ''],
                ['title' => 'Tab 2', 'icon' => '', 'content' => ''],
                ['title' => 'Tab 3', 'icon' => '', 'content' => ''],
            ],
        ];
    }
}
