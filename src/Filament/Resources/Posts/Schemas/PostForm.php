<?php

namespace Miran\Mksine\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Miran\Mksine\Core\Hooks\FormHookManager;
use Miran\Mksine\Filament\Forms\Components\CKEditor;
use Miran\Mksine\Filament\Forms\Components\MediaPicker;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        $schema = $schema
            ->components([
                Section::make('Content')
                    ->schema([
                        TextInput::make('title')
                            ->label('Title')
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
                        Textarea::make('excerpt')
                            ->label('Excerpt')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Settings')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                                'archived' => 'Archived',
                            ])
                            ->default('draft')
                            ->required()
                            ->native(false),
                        Select::make('author_id')
                            ->label('Author')
                            ->relationship('author', 'name')
                            ->required()
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Select::make('categories')
                            ->label('Categories')
                            ->relationship(
                                'categories',
                                'name',
                                fn ($query) => $query->where('is_active', true)->orderBy('categories.sort_order')
                            )
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->columnSpanFull(),
                        DateTimePicker::make('published_at')
                            ->label('Published At')
                            ->displayFormat('d/m/Y H:i')
                            ->native(false),

                        MediaPicker::make('featured_image')
                            ->isRelation(false)
                            ->label('Featured Image')
                            ->collection('featured_image')
                            ->acceptedFileTypes(['image/*'])
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                CKEditor::make('content')
                    ->label('Content')
                    ->required()
                    ->placeholder('Enter post content...')
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
                    ->columnSpanFull()
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),
            ]);

        // Apply form hooks
        $formHookManager = app(FormHookManager::class);

        return $formHookManager->apply('post.form', $schema);
    }
}
