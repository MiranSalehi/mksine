<?php

namespace Miran\Mksine\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Miran\Mksine\Core\Hooks\FormHookManager;
use Miran\Mksine\Filament\Forms\Components\CKEditor;
use Miran\Mksine\Filament\Forms\Components\MediaPicker;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        $schema = $schema
            ->components([
                Section::make('Category Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpanFull(),
                        MediaPicker::make('image')
                            ->label('Image')
                            ->isRelation(false)
                            ->collection('category_image')
                            ->acceptedFileTypes(['image/*'])
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Settings')
                    ->schema([
                        Select::make('parent_id')
                            ->label('Parent Category')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('No parent'),
                        TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(3)
                    ->collapsible(),
                CKEditor::make('description')
                    ->label('Description')
                    ->required()
                    ->columnSpanFull(),
                Section::make('SEO')
                    ->schema([
                        TextInput::make('meta_title')
                            ->label('Meta Title')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('meta_description')
                            ->label('Meta Description')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->columnSpanFull()
                    ->collapsible()
                    ->collapsed(),
            ]);

        // Apply form hooks
        $formHookManager = app(FormHookManager::class);

        return $formHookManager->apply('category.form', $schema);
    }
}
