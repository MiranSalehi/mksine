<?php

namespace Miran\Mksine\Filament\Resources\Media\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MediaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('url')
                    ->label('Preview')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => $record->url ?? null),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->limit(30),
                TextColumn::make('file_name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(true, true)
                    ->color('gray')
                    ->limit(30),
                TextColumn::make('mime_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_starts_with($state, 'image/') => 'success',
                        str_starts_with($state, 'video/') => 'danger',
                        str_starts_with($state, 'audio/') => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('size')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => static::formatBytes($state))
                    ->sortable()
                    ->color('gray'),
                TextColumn::make('width')
                    ->label('Dimensions')
                    ->formatStateUsing(fn ($record) => $record->width && $record->height
                        ? "{$record->width} × {$record->height}"
                        : '-')
                    ->color('gray'),
                TextColumn::make('attachments_count')
                    ->label('Attachments')
                    ->counts('attachments')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('gray'),

            ])
            ->filters([
                SelectFilter::make('mime_type')
                    ->label('Type')
                    ->options([
                        'image' => 'Images',
                        'video' => 'Videos',
                        'audio' => 'Audio',
                        'application' => 'Documents',
                    ])
                    ->query(function ($query, $state) {
                        $state = $state['value'] ?? null;

                        return $query->when(! empty($state), function ($q) use ($state) {
                            $q->where('mime_type', 'like', "{$state}/%");
                        });
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
