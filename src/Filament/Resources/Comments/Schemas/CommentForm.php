<?php

namespace Miran\Mksine\Filament\Resources\Comments\Schemas;

use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Miran\Mksine\Core\Hooks\FormHookManager;
use Miran\Mksine\Models\Comment;
use Miran\Mksine\Models\Post;

class CommentForm
{
    public static function configure(Schema $schema): Schema
    {
        $schema = $schema
            ->components([
                Section::make(__('mksine::comments.comment_information'))
                    ->key('comment_information')
                    ->schema([
                        MorphToSelect::make('commentable')
                            ->label(__('mksine::comments.commentable'))
                            ->types(static function (): array {
                                return collect(config('mksine.commentable_types', [Post::class]))
                                    ->filter(static fn (string $class): bool => class_exists($class))
                                    ->map(static fn (string $class): Type => Type::make($class)->titleAttribute('title'))
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->columnSpanFull(),
                        Select::make('parent_id')
                            ->label(__('mksine::comments.reply_to'))
                            ->relationship(
                                'parent',
                                'content',
                                modifyQueryUsing: static function ($query, Get $get): void {
                                    $type = $get('commentable_type');
                                    $id = $get('commentable_id');
                                    if (filled($type) && filled($id)) {
                                        $query->where('commentable_type', $type)
                                            ->where('commentable_id', $id);
                                    }
                                },
                            )
                            ->getOptionLabelFromRecordUsing(fn (Comment $record): string => \Illuminate\Support\Str::limit($record->content, 50))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder(__('mksine::comments.none_root'))
                            ->columnSpanFull(),
                        Textarea::make('content')
                            ->label(__('mksine::comments.content'))
                            ->required()
                            ->rows(4)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('mksine::comments.author_information'))
                    ->key('author_information')
                    ->schema([
                        Select::make('user_id')
                            ->label(__('mksine::comments.registered_user'))
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder(__('mksine::comments.guest_comment')),
                        TextInput::make('author_name')
                            ->label(__('mksine::comments.guest_name'))
                            ->maxLength(255)
                            ->placeholder(__('mksine::comments.guest_name_placeholder')),
                        TextInput::make('author_email')
                            ->label(__('mksine::comments.guest_email'))
                            ->email()
                            ->maxLength(255)
                            ->placeholder(__('mksine::comments.guest_email_placeholder')),
                    ])
                    ->columns(3)
                    ->collapsible(),
                Section::make(__('mksine::comments.rating_status'))
                    ->key('rating_status')
                    ->schema([
                        Select::make('rating')
                            ->label(__('mksine::comments.rating'))
                            ->options([
                                1 => __('mksine::comments.rating_1_star'),
                                2 => __('mksine::comments.rating_2_stars'),
                                3 => __('mksine::comments.rating_3_stars'),
                                4 => __('mksine::comments.rating_4_stars'),
                                5 => __('mksine::comments.rating_5_stars'),
                            ])
                            ->native(false)
                            ->placeholder(__('mksine::comments.no_rating')),
                        Select::make('status')
                            ->label(__('mksine::comments.status'))
                            ->options([
                                Comment::STATUS_PENDING => __('mksine::comments.status_pending'),
                                Comment::STATUS_APPROVED => __('mksine::comments.status_approved'),
                                Comment::STATUS_SPAM => __('mksine::comments.status_spam'),
                                Comment::STATUS_TRASH => __('mksine::comments.status_trash'),
                            ])
                            ->default(Comment::STATUS_PENDING)
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make(__('mksine::comments.technical_info'))
                    ->key('technical_info')
                    ->schema([
                        TextInput::make('ip_address')
                            ->label(__('mksine::comments.ip_address'))
                            ->maxLength(45)
                            ->disabled()
                            ->dehydrated(false),
                        Textarea::make('user_agent')
                            ->label(__('mksine::comments.user_agent'))
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
