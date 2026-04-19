<?php

namespace Miran\Mksine\Filament\Resources\Pages\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Miran\Mksine\Filament\Resources\Pages\PageResource;
use Miran\Mksine\Models\Page;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('duplicate')
                ->label(__('mksine::pages.duplicate'))
                ->icon(Heroicon::Square2Stack)
                ->color('gray')
                ->authorize('replicate')
                ->requiresConfirmation()
                ->modalHeading(__('mksine::pages.duplicate_modal_heading'))
                ->modalDescription(__('mksine::pages.duplicate_modal_description'))
                ->action(function (Page $record, Action $action): void {
                    $duplicate = $record->duplicateAsDraft(Auth::id());
                    Notification::make()
                        ->title(__('mksine::pages.duplicate_success'))
                        ->success()
                        ->send();
                    $action->redirect(PageResource::getUrl('edit', ['record' => $duplicate]));
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();

        return $data;
    }
}
