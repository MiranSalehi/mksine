<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Miran\Mksine\Core\Updater\SuperAdminGate;
use Miran\Mksine\Core\Updater\UpdateResult;
use Miran\Mksine\Core\Updater\Updaters\CoreUpdater;
use Miran\Mksine\Core\Updater\UpdateRunner;
use Miran\Mksine\Support\LivewireUploadConfiguration;
use Miran\Mksine\Support\UploadLimits;

/**
 * Super-Admin-only Filament page for updating the core miran/mksine package.
 *
 * This UI posts the uploaded ZIP to {@see CoreUpdater} IN-PROCESS. For the
 * safest path, operators should SSH and run `php artisan mksine:update`; this
 * page is offered for scenarios where CLI access is inconvenient, but it
 * carries the inherent risk of replacing code mid-request (see CoreUpdater's
 * class doc for how we mitigate it via pre-loading).
 */
class SystemUpdate extends Page
{
    use HasPageShield;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected string $view = 'mksine::filament.pages.system-update';

    protected static ?string $slug = 'system-update';

    protected static ?int $navigationSort = 9999;

    public ?UpdateResult $lastResult = null;

    public static function getNavigationLabel(): string
    {
        return __('mksine::updater.core_navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('mksine::common.system');
    }

    public function getTitle(): string
    {
        return __('mksine::updater.core_title');
    }

    public function getSubheading(): ?string
    {
        return __('mksine::updater.core_subheading', [
            'version' => (string) config('mksine.version', '0.0.0'),
        ]);
    }

    public function getCurrentVersion(): string
    {
        return (string) config('mksine.version', '0.0.0');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload_core')
                ->label(__('mksine::updater.upload_core'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->visible(fn () => SuperAdminGate::check() && (bool) config('mksine.updater.enabled', true))
                ->schema([
                    Placeholder::make('warning')
                        ->label(__('mksine::updater.core_risk_label'))
                        ->content(__('mksine::updater.core_risk_body')),

                    FileUpload::make('core_file')
                        ->label(__('mksine::updater.zip_file'))
                        ->helperText(__('mksine::updater.core_zip_helper'))
                        ->acceptedFileTypes(LivewireUploadConfiguration::zipAcceptedMimeTypes())
                        ->maxSize(UploadLimits::updaterMaxZipKb())
                        ->required()
                        ->storeFiles(false),

                    Toggle::make('force')
                        ->label(__('mksine::updater.force_toggle'))
                        ->helperText(__('mksine::updater.force_helper'))
                        ->default(false),
                ])
                ->action(function (array $data): void {
                    $this->handleUpload($data);
                }),
        ];
    }

    public function handleUpload(array $data): void
    {
        SuperAdminGate::authorize();

        $path = $this->resolveUploadedPath($data['core_file'] ?? null);
        if ($path === null) {
            Notification::make()
                ->title(__('mksine::updater.upload_failed'))
                ->body(__('mksine::updater.invalid_upload'))
                ->danger()
                ->send();

            return;
        }

        $updater = new CoreUpdater(new UpdateRunner);
        $this->lastResult = $updater->update($path, (bool) ($data['force'] ?? false));

        if ($this->lastResult->success) {
            Notification::make()
                ->title(__('mksine::updater.core_updated'))
                ->body(__('mksine::updater.core_updated_body', [
                    'from' => $this->lastResult->fromVersion ?? '?',
                    'to' => $this->lastResult->toVersion ?? '?',
                ]))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(__('mksine::updater.update_failed'))
                ->body($this->lastResult->errorMessage)
                ->danger()
                ->send();
        }
    }

    private function resolveUploadedPath(mixed $uploaded): ?string
    {
        if ($uploaded instanceof TemporaryUploadedFile) {
            return $uploaded->getRealPath();
        }
        if ($uploaded instanceof UploadedFile) {
            return $uploaded->getRealPath();
        }
        if (is_string($uploaded) && $uploaded !== '') {
            $full = storage_path('app/'.$uploaded);

            return is_file($full) ? $full : null;
        }

        return null;
    }
}
