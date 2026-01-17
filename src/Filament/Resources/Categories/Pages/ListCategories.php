<?php

namespace Miran\Mksine\Filament\Resources\Categories\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Miran\Mksine\Core\Hooks\PageHookManager;
use Miran\Mksine\Filament\Resources\Categories\CategoryResource;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [
            CreateAction::make(),
        ];

        // Apply page hooks
        $pageHookManager = app(PageHookManager::class);

        return $pageHookManager->applyHeaderActions('category.list', $actions);
    }
}
