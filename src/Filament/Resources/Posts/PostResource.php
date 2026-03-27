<?php

namespace Miran\Mksine\Filament\Resources\Posts;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Miran\Mksine\Core\Hooks\ResourceHookManager;
use Miran\Mksine\Filament\Resources\Posts\Pages\CreatePost;
use Miran\Mksine\Filament\Resources\Posts\Pages\EditPost;
use Miran\Mksine\Filament\Resources\Posts\Pages\ListPosts;
use Miran\Mksine\Filament\Resources\Posts\Schemas\PostForm;
use Miran\Mksine\Filament\Resources\Posts\Tables\PostsTable;
use Miran\Mksine\Models\Post;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    public static function getNavigationLabel(): string
    {
        return __('mksine::posts.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('mksine::posts.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('mksine::posts.plural_model_label');
    }

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('mksine::common.content');
    }

    public static function form(Schema $schema): Schema
    {
        return PostForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PostsTable::configure($table);
    }

    public static function getRelations(): array
    {
        $relations = [
            //
        ];

        // Apply resource hooks
        $resourceHookManager = app(ResourceHookManager::class);

        return $resourceHookManager->applyRelations('post.resource', $relations);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
