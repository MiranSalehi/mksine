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
                Section::make(__('mksine::categories.category_information'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('mksine::categories.name'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                        TextInput::make('slug')
                            ->label(__('mksine::categories.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpanFull(),
                        MediaPicker::make('image')
                            ->label(__('mksine::categories.image'))
                            ->isRelation(false)
                            ->collection('category_image')
                            ->acceptedFileTypes(['image/*'])
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('mksine::categories.settings'))
                    ->schema([
                        Select::make('parent_id')
                            ->label(__('mksine::categories.parent_category'))
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder(__('mksine::categories.no_parent')),
                        TextInput::make('sort_order')
                            ->label(__('mksine::categories.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->required(),
                        Toggle::make('is_active')
                            ->label(__('mksine::categories.active'))
                            ->default(true)
                            ->required(),
                    ])
                    ->columns(3)
                    ->collapsible(),
                CKEditor::make('description')
                    ->label(__('mksine::categories.description'))
                    ->required()
                    ->columnSpanFull(),
                Section::make(__('mksine::common.seo'))
                    ->schema([
                        TextInput::make('meta_title')
                            ->label(__('mksine::categories.meta_title'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('meta_description')
                            ->label(__('mksine::categories.meta_description'))
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
