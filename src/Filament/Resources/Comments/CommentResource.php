<?php

namespace Miran\Mksine\Filament\Resources\Comments;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Miran\Mksine\Core\Hooks\ResourceHookManager;
use Miran\Mksine\Filament\Resources\Comments\Pages\CreateComment;
use Miran\Mksine\Filament\Resources\Comments\Pages\EditComment;
use Miran\Mksine\Filament\Resources\Comments\Pages\ListComments;
use Miran\Mksine\Filament\Resources\Comments\Schemas\CommentForm;
use Miran\Mksine\Filament\Resources\Comments\Tables\CommentTable;
use Miran\Mksine\Models\Comment;

class CommentResource extends Resource
{
    protected static ?string $model = Comment::class;

    protected static ?string $navigationLabel = 'Comments';

    protected static ?string $modelLabel = 'Comment';

    protected static ?string $pluralModelLabel = 'Comments';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 4;

    public static function getNavigationGroup(): ?string
    {
        return __('Content');
    }

    public static function form(Schema $schema): Schema
    {
        return CommentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommentTable::configure($table);
    }

    public static function getRelations(): array
    {
        $relations = [
            //
        ];

        // Apply resource hooks
        $resourceHookManager = app(ResourceHookManager::class);

        return $resourceHookManager->applyRelations('comment.resource', $relations);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComments::route('/'),
            'create' => CreateComment::route('/create'),
            'edit' => EditComment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
