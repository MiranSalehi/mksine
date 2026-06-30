<?php

namespace Miran\Mksine\Filament\Resources\Media\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Miran\Mksine\Support\UploadLimits;

class MediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('mksine::media.file_information'))
                    ->schema([
                        // Hidden disk field for create page (needed for mutateFormDataBeforeCreate)
                        Select::make('disk')
                            ->label(__('mksine::media.disk'))
                            ->options(function () {
                                $disks = config('filesystems.disks', []);
                                foreach ($disks as $disk => $config) {
                                    $disks[$disk] = $config['name'] ?? $disk;
                                }

                                return $disks;
                            })
                            ->default('public')
                            ->required()
                            ->hiddenLabel()
                            ->visibleOn('create')
                            ->dehydrated(),
                        FileUpload::make('file')
                            ->label(__('mksine::media.file'))
                            ->required()
                            ->acceptedFileTypes(['image/*', 'video/*', 'audio/*', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(UploadLimits::mediaMaxKb())
                            ->disk(fn ($get) => $get('disk') ?? 'public')
                            ->directory('media')
                            ->visibility('public')
                            ->storeFileNamesIn('file_name')
                            ->deletable()
                            ->downloadable()
                            ->previewable()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->visibleOn(['create', 'edit'])
                            ->afterStateUpdated(function ($state, $set, $get, $record) {
                                // Update path when file is uploaded/changed in edit mode
                                if ($record && $state) {
                                    // Handle both array (multiple) and string (single) states
                                    $fileRelativePath = is_array($state)
                                        ? (count($state) > 0 ? $state[0] : null)
                                        : $state;

                                    if ($fileRelativePath && is_string($fileRelativePath)) {
                                        // Ensure path includes 'media/' prefix
                                        $fullPath = str_starts_with($fileRelativePath, 'media/')
                                            ? $fileRelativePath
                                            : 'media/'.$fileRelativePath;
                                        $set('path', $fullPath);
                                    }
                                }
                            })
                            ->live(),
                    ])
                    ->columns(1),
                Section::make(__('mksine::media.details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('mksine::media.name'))
                            ->columnSpanFull()
                            ->maxLength(255)
                            ->visibleOn(['create', 'edit']),
                        Select::make('disk')
                            ->label(__('mksine::media.disk'))
                            ->options(function () {
                                $disks = config('filesystems.disks', []);
                                foreach ($disks as $disk => $config) {
                                    $disks[$disk] = $config['name'] ?? $disk;
                                }

                                return $disks;
                            })
                            ->default('public')
                            ->required()
                            ->columnSpanFull()
                            ->live()
                            ->visibleOn('edit')
                            ->afterStateUpdated(function ($state, $set, $get) {
                                // Update path and URL when disk changes
                                if ($get('file_name')) {
                                    $fileName = $get('file_name');
                                    $set('path', 'media/'.$fileName);

                                    // Generate URL based on disk configuration
                                    $diskConfig = config("filesystems.disks.{$state}", []);
                                    $diskUrl = $diskConfig['url'] ?? null;

                                    if ($diskUrl) {
                                        // Use URL from filesystem config
                                        $url = rtrim($diskUrl, '/').'/media/'.$fileName;
                                        $set('url', $url);
                                    } else {
                                        // Try to get URL from Storage facade
                                        try {
                                            /** @var FilesystemAdapter $storage */
                                            $storage = Storage::disk($state);
                                            if (method_exists($storage, 'url')) {
                                                $url = $storage->url('media/'.$fileName);
                                                $set('url', $url);
                                            } else {
                                                $set('url', 'media/'.$fileName);
                                            }
                                        } catch (\Exception $e) {
                                            // If disk doesn't support URL, use path
                                            $set('url', 'media/'.$fileName);
                                        }
                                    }
                                }
                            }),
                        TextInput::make('file_name')
                            ->label(__('mksine::media.file_name'))
                            ->disabled()
                            ->columnSpanFull()
                            ->dehydrated()
                            ->visibleOn('edit'),
                        TextInput::make('mime_type')
                            ->label(__('mksine::media.mime_type'))
                            ->disabled()
                            ->dehydrated()
                            ->visibleOn('edit'),
                        TextInput::make('size')
                            ->label(__('mksine::media.size_bytes'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->visibleOn('edit'),
                        TextInput::make('width')
                            ->label(__('mksine::media.width'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->visibleOn('edit'),
                        TextInput::make('height')
                            ->label(__('mksine::media.height'))
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->visibleOn('edit'),
                        TextInput::make('path')
                            ->label(__('mksine::media.path'))
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->columnSpanFull()
                            ->visibleOn('edit'),
                        TextInput::make('url')
                            ->label(__('mksine::media.url'))
                            ->disabled()
                            ->dehydrated()
                            ->columnSpanFull()
                            ->visibleOn('edit'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->visibleOn('edit'),
            ]);
    }
}
