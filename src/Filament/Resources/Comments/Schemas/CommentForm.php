<?php

namespace Miran\Mksine\Filament\Resources\Comments\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Miran\Mksine\Core\Hooks\FormHookManager;
use Miran\Mksine\Models\Comment;

class CommentForm
{
    public static function configure(Schema $schema): Schema
    {
        $schema = $schema
            ->components([
                Section::make('Comment Information')
                    ->schema([
                        Select::make('post_id')
                            ->label('Post')
                            ->relationship('post', 'title')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->columnSpanFull(),
                        Select::make('parent_id')
                            ->label('Reply to')
                            ->relationship('parent', 'content', fn ($query) => $query->limit(50))
                            ->getOptionLabelFromRecordUsing(fn (Comment $record) => \Illuminate\Support\Str::limit($record->content, 50))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('None (root comment)')
                            ->columnSpanFull(),
                        Textarea::make('content')
                            ->label('Content')
                            ->required()
                            ->rows(4)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Author Information')
                    ->schema([
                        Select::make('user_id')
                            ->label('Registered User')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Guest comment'),
                        TextInput::make('author_name')
                            ->label('Guest Name')
                            ->maxLength(255)
                            ->placeholder('For guest comments'),
                        TextInput::make('author_email')
                            ->label('Guest Email')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('For guest comments'),
                    ])
                    ->columns(3)
                    ->collapsible(),
                Section::make('Rating & Status')
                    ->schema([
                        Select::make('rating')
                            ->label('Rating')
                            ->options([
                                1 => '1 Star',
                                2 => '2 Stars',
                                3 => '3 Stars',
                                4 => '4 Stars',
                                5 => '5 Stars',
                            ])
                            ->native(false)
                            ->placeholder('No rating'),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                Comment::STATUS_PENDING => 'Pending',
                                Comment::STATUS_APPROVED => 'Approved',
                                Comment::STATUS_SPAM => 'Spam',
                                Comment::STATUS_TRASH => 'Trash',
                            ])
                            ->default(Comment::STATUS_PENDING)
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('Technical Info')
                    ->schema([
                        TextInput::make('ip_address')
                            ->label('IP Address')
                            ->maxLength(45)
                            ->disabled()
                            ->dehydrated(false),
                        Textarea::make('user_agent')
                            ->label('User Agent')
                            ->rows(2)
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);

        // Apply form hooks
        $formHookManager = app(FormHookManager::class);

        return $formHookManager->apply('comment.form', $schema);
    }
}
