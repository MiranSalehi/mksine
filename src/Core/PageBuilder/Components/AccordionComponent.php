<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;
use Miran\Mksine\Filament\Forms\Components\CKEditor;

class AccordionComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'accordion';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_accordion');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-queue-list';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_INTERACTIVE;
    }

    public static function getDescription(): string
    {
        return __('mksine::page_builder.component_labels.desc_accordion');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('heading')
                ->label(__('mksine::page_builder.component_labels.section_heading'))
                ->maxLength(255)
                ->placeholder(__('mksine::page_builder.component_labels.frequently_asked'))
                ->columnSpanFull(),
            Select::make('style')
                ->label(__('mksine::page_builder.component_labels.style'))
                ->options([
                    'simple' => __('mksine::page_builder.component_labels.simple'),
                    'bordered' => __('mksine::page_builder.component_labels.bordered'),
                    'separated' => __('mksine::page_builder.component_labels.separated_cards'),
                ])
                ->default('bordered')
                ->native(false),
            Toggle::make('allow_multiple')
                ->label(__('mksine::page_builder.component_labels.allow_multiple_open'))
                ->helperText(__('mksine::page_builder.component_labels.allow_multiple_help'))
                ->default(false),
            Toggle::make('first_open')
                ->label(__('mksine::page_builder.component_labels.first_item_open_default'))
                ->default(true),
            Select::make('icon_position')
                ->label(__('mksine::page_builder.component_labels.icon_position'))
                ->options([
                    'left' => __('mksine::page_builder.component_labels.left'),
                    'right' => __('mksine::page_builder.component_labels.right'),
                ])
                ->default('right')
                ->native(false),
            Repeater::make('items')
                ->label(__('mksine::page_builder.component_labels.faq_items'))
                ->schema([
                    TextInput::make('question')
                        ->label(__('mksine::page_builder.component_labels.question'))
                        ->required()
                        ->maxLength(500)
                        ->columnSpanFull(),
                    CKEditor::make('answer')
                        ->label(__('mksine::page_builder.component_labels.answer'))
                        ->required()
                        ->columnSpanFull(),
                ])
                ->defaultItems(3)
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                ->columnSpanFull(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'heading' => '',
            'style' => 'bordered',
            'allow_multiple' => false,
            'first_open' => true,
            'icon_position' => 'right',
            'items' => [],
        ];
    }
}
