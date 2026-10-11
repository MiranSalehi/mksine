<?php

namespace Miran\Mksine\Filament\Resources\PageBuilderSections\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PageBuilderSectionTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('mksine::page_builder.section_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('mksine::page_builder.section_updated'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
