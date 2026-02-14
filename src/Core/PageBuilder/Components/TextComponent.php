<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Filament\Forms\Components\RichEditor;
use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;

class TextComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'text';
    }

    public static function getName(): string
    {
        return __('Text');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public static function getCategory(): string
    {
        return self::CATEGORY_CONTENT;
    }

    public static function getDescription(): string
    {
        return __('Add rich text content with formatting.');
    }

    public static function getSchema(): array
    {
        return [
            RichEditor::make('content')
                ->label(__('Content'))
                ->required()
                ->toolbarButtons([
                    'bold',
                    'italic',
                    'underline',
                    'strike',
                    'link',
                    'orderedList',
                    'bulletList',
                    'h2',
                    'h3',
                    'blockquote',
                    'codeBlock',
                    'redo',
                    'undo',
                ])
                ->columnSpanFull(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'content' => '',
        ];
    }
}
