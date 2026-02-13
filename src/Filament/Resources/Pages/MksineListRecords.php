<?php

namespace Miran\Mksine\Filament\Resources\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Miran\Mksine\Core\Hooks\PageHookManager;

/**
 * Base ListRecords: CreateAction in page header (above table), like default Filament.
 * Table header stays empty (getTableHeaderActions returns []) to avoid duplicate.
 */
abstract class MksineListRecords extends ListRecords
{
    protected function getHeaderActions(): array
    {
        $actions = [];

        if (static::getResource()::hasPage('create')) {
            $actions[] = CreateAction::make();
        }

        if ($hookName = $this->getHeaderActionsHookName()) {
            $actions = app(PageHookManager::class)->applyHeaderActions($hookName, $actions);
        }

        return $actions;
    }

    protected function getTableHeaderActions(): array
    {
        return [];
    }

    /**
     * Return the hook name for header actions (e.g. 'page.list') or null to skip.
     */
    protected function getHeaderActionsHookName(): ?string
    {
        return null;
    }
}
