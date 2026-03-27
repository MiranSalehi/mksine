<?php

namespace Miran\Mksine\Core\PageBuilder\Components;

use Miran\Mksine\Core\PageBuilder\BaseBuilderComponent;
use Miran\Mksine\Filament\Forms\Components\CKEditor;

class TextComponent extends BaseBuilderComponent
{
    public static function getType(): string
    {
        return 'text';
    }

    public static function getName(): string
    {
        return __('mksine::page_builder.component_labels.name_text');
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
        return __('mksine::page_builder.component_labels.desc_text');
    }

    public static function getSchema(): array
    {
        return [
            CKEditor::make('content')
                ->label(__('mksine::page_builder.component_labels.content'))
                ->required()
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
