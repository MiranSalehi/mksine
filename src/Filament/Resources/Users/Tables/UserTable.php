<?php

namespace Miran\Mksine\Filament\Resources\Users\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Miran\Mksine\Core\Hooks\TableHookManager;

class UserTable
{
    public static function configure(Table $table): Table
    {
        $table = $table
            ->columns([
                ImageColumn::make('avatar')
                    ->label(__('Avatar'))
                    ->circular()
                    ->getStateUsing(fn ($record) => $record->avatar_url)
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF'),

                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->bio ? \Illuminate\Support\Str::limit($record->bio, 50) : null),

                TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('formatted_phone')
                    ->label(__('Phone'))
                    ->placeholder('—'),

                TextColumn::make('date_of_birth')
                    ->label(__('Date of Birth'))
                    ->date()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ]);

        // Apply table hooks
        $hookManager = app(TableHookManager::class);

        return $hookManager->apply('user.table', $table);
    }
}
