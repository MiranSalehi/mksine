<?php

namespace Miran\Mksine\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Miran\Mksine\Core\Hooks\FormHookManager;
use Miran\Mksine\Filament\Forms\Components\CKEditor;
use Miran\Mksine\Filament\Forms\Components\PageBuilderField;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        $formHookManager = app(FormHookManager::class);

        $schema = $schema
            ->components([
                Section::make('Page Information')
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
                        Select::make('type')
                            ->label('Page Type')
                            ->options([
                                'simple' => 'Simple',
                                'builder' => 'Builder',
                            ])
                            ->default('simple')
                            ->required()
                            ->native(false)
                            ->live(),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                                'scheduled' => 'Scheduled',
                            ])
                            ->default('draft')
                            ->required()
                            ->native(false)
                            ->live(),
                        DateTimePicker::make('published_at')
                            ->label('Publish Date')
                            ->visible(fn ($get) => $get('status') === 'scheduled')
                            ->required(fn ($get) => $get('status') === 'scheduled')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Content')
                    ->columnSpanFull()
                    ->schema([
                        CKEditor::make('content')
                            ->label('Content')
                            ->live(false)
                            ->required(fn ($get) => $get('type') === 'simple')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($get) => $get('type') === 'simple'),
                Section::make('Page Builder')
                    ->columnSpanFull()
                    ->schema([
                        PageBuilderField::make('builder_payload')
                            ->label('')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($get) => $get('type') === 'builder'),
                Section::make('SEO')
                    ->schema([
                        TextInput::make('meta_title')
                            ->label('Meta Title')
                            ->maxLength(60)
                            ->helperText('Recommended: 50-60 characters')
                            ->columnSpanFull(),
                        Textarea::make('meta_description')
                            ->label('Meta Description')
                            ->maxLength(160)
                            ->rows(3)
                            ->helperText('Recommended: 150-160 characters')
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);

        // Apply form hooks
        return $formHookManager->apply('page.form', $schema);
    }
}
