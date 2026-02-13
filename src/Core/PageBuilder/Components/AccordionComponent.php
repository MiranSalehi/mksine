<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class AccordionComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'accordion';
    }

    public static function getName(): string
    {
        return __('FAQ Accordion');
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
        return __('Collapsible FAQ sections with questions and answers.');
    }

    public static function getSchema(): array
    {
        return [
            TextInput::make('heading')
                ->label(__('Section Heading'))
                ->maxLength(255)
                ->placeholder(__('Frequently Asked Questions'))
                ->columnSpanFull(),
            Select::make('style')
                ->label(__('Style'))
                ->options([
                    'simple' => __('Simple'),
                    'bordered' => __('Bordered'),
                    'separated' => __('Separated Cards'),
                ])
                ->default('bordered')
                ->native(false),
            Toggle::make('allow_multiple')
                ->label(__('Allow Multiple Open'))
                ->helperText(__('Allow multiple items to be open at once'))
                ->default(false),
            Toggle::make('first_open')
                ->label(__('First Item Open by Default'))
                ->default(true),
            Select::make('icon_position')
                ->label(__('Icon Position'))
                ->options([
                    'left' => __('Left'),
                    'right' => __('Right'),
                ])
                ->default('right')
                ->native(false),
            Repeater::make('items')
                ->label(__('FAQ Items'))
                ->schema([
                    TextInput::make('question')
                        ->label(__('Question'))
                        ->required()
                        ->maxLength(500)
                        ->columnSpanFull(),
                    RichEditor::make('answer')
                        ->label(__('Answer'))
                        ->required()
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'link',
                            'orderedList',
                            'bulletList',
                        ])
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
