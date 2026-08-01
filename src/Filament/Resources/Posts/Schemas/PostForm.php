<?php

namespace Miran\Mksine\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Miran\Mksine\Core\Hooks\FormHookManager;
use Miran\Mksine\Filament\Forms\Components\CKEditor;
use Miran\Mksine\Filament\Forms\Components\MediaPicker;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        $schema = $schema
            ->components([
                Section::make(__('mksine::common.content'))
                    ->key('content')
                    ->schema([
                        TextInput::make('title')
                            ->label(__('mksine::posts.title'))
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                        TextInput::make('slug')
                            ->label(__('mksine::posts.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpanFull(),
                        Textarea::make('excerpt')
                            ->label(__('mksine::posts.excerpt'))
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('mksine::categories.settings'))
                    ->key('settings')
                    ->schema([
                        Select::make('status')
                            ->label(__('mksine::posts.status'))
                            ->options([
                                'draft' => __('mksine::posts.status_draft'),
                                'published' => __('mksine::posts.status_published'),
                                'archived' => __('mksine::posts.status_archived'),
                            ])
                            ->default('draft')
                            ->required()
                            ->native(false),
                        Select::make('author_id')
                            ->label(__('mksine::posts.author'))
                            ->relationship('author', 'name')
                            ->required()
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->preload()
                            ->native(false),
                        SelectTree::make('categories')
                            ->label(__('mksine::posts.categories'))
                            ->relationship(
                                relationship: 'categories',
                                titleAttribute: 'name',
                                parentAttribute: 'parent_id',
                                modifyQueryUsing: fn ($query) => $query->where('is_active', true)->orderBy('categories.sort_order')
                            )
                            ->searchable()
                            ->enableBranchNode()
                            ->columnSpanFull(),
                        DateTimePicker::make('published_at')
                            ->label(__('mksine::posts.published_at'))
                            ->displayFormat('d/m/Y H:i')
                            ->native(false),

                        MediaPicker::make('featured_image')
                            ->isRelation(false)
                            ->label(__('mksine::posts.featured_image'))
                            ->collection('featured_image')
                            ->acceptedFileTypes(['image/*'])
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible(),

                CKEditor::make('content')
                    ->label(__('mksine::posts.content'))
                    ->required()
                    ->placeholder(__('mksine::posts.content_placeholder'))
                    ->columnSpanFull(),

                Section::make(__('mksine::common.seo'))
                    ->key('seo')
                    ->schema([
                        TextInput::make('meta_title')
                            ->label(__('mksine::posts.meta_title'))
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('meta_description')
                            ->label(__('mksine::posts.meta_description'))
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
