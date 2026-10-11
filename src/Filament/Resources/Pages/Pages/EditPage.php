<?php

namespace Miran\Mksine\Filament\Resources\Pages\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Miran\Mksine\Core\Hooks\ContentFormHooks;
use Miran\Mksine\Core\PageBuilder\PageBuilderRevisionStore;
use Miran\Mksine\Filament\Resources\Pages\PageResource;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\PageBuilderRevision;
use Miran\Mksine\Support\MksDateFormatter;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('restoreBuilderRevision')
                ->label(__('mksine::pages.revisions'))
                ->icon(Heroicon::Clock)
                ->color('gray')
                ->visible(fn (Page $record): bool => $record->usesBuilder() && $record->builderRevisions()->exists())
                ->form([
                    Select::make('revision_id')
                        ->label(__('mksine::pages.revision_choose'))
                        ->options(function (Page $record): array {
                            return $record->builderRevisions()
                                ->latest('id')
                                ->limit(PageBuilderRevisionStore::KEEP)
                                ->get()
                                ->mapWithKeys(function (PageBuilderRevision $revision): array {
                                    $when = MksDateFormatter::format($revision->created_at) ?? (string) $revision->getKey();

                                    return [$revision->getKey() => $when.' #'.$revision->getKey()];
                                })
                                ->all();
                        })
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data, Page $record): void {
                    app(PageBuilderRevisionStore::class)->restore($record, (int) $data['revision_id'], Auth::id());
                    $this->record->refresh();
                    $this->fillForm();
                    Notification::make()
                        ->title(__('mksine::pages.revision_restored'))
                        ->success()
                        ->send();
                }),
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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (($data['type'] ?? null) === 'builder' && blank($data['builder_content_width'] ?? null)) {
            $data['builder_content_width'] = 'full';
        }

        return ContentFormHooks::fill($data, 'page', $this->record);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();

        return ContentFormHooks::mutate($data, 'page', $this->record);
    }

    protected function beforeSave(): void
    {
        $record = $this->getRecord();
        if ($record instanceof Page) {
            app(PageBuilderRevisionStore::class)->record($record, Auth::id());
        }
    }

    protected function afterSave(): void
    {
        ContentFormHooks::saved($this->record, 'page');

        $record = $this->getRecord();
        if ($record instanceof Page) {
            app(PageBuilderRevisionStore::class)->record($record->refresh(), Auth::id());
        }
    }
}
