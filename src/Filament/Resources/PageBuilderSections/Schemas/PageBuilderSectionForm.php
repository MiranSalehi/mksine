<?php

namespace Miran\Mksine\Filament\Resources\PageBuilderSections\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Miran\Mksine\Filament\Forms\Components\PageBuilderField;

class PageBuilderSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('mksine::page_builder.section_name'))
                ->required()
                ->maxLength(255),
            PageBuilderField::make('builder_payload')
                ->label(__('mksine::page_builder.field_label')),
        ]);
    }
}
