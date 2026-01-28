<?php

namespace Miran\Mksine\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Miran\Mksine\Core\Hooks\FormHookManager;
use Miran\Mksine\Filament\Forms\Components\CKEditor;

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
                            ->live()
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
                            ->visible(fn ($get) => $get('type') === 'simple')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($get) => $get('type') === 'simple'),
            ]);

        // Apply form hooks
        return $formHookManager->apply('page.form', $schema);
    }
}
