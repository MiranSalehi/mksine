<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Resources\GeoStates\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Miran\Mksine\Enums\GeoSource;
use Miran\Mksine\Models\GeoCity;
use Miran\Mksine\Models\GeoState;

class CitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'cities';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('mksine::geo.cities.plural_model_label');
    }

    public static function getModelLabel(): ?string
    {
        return __('mksine::geo.cities.model_label');
    }

    public static function getPluralModelLabel(): ?string
    {
        return __('mksine::geo.cities.plural_model_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('mksine::geo.cities.name'))
                ->required()
                ->maxLength(255),
            TextInput::make('native')
                ->label(__('mksine::geo.cities.native'))
                ->maxLength(255),
            Toggle::make('is_visible')
                ->label(__('mksine::geo.cities.is_visible'))
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->heading(__('mksine::geo.cities.plural_model_label'))
            ->description(__('mksine::geo.cities.relation_help'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('mksine::geo.cities.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('native')
                    ->label(__('mksine::geo.cities.native'))
                    ->searchable(),
                TextColumn::make('source')
                    ->label(__('mksine::geo.cities.source'))
                    ->badge(),
                IconColumn::make('is_visible')
                    ->label(__('mksine::geo.cities.is_visible'))
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        /** @var GeoState $state */
                        $state = $this->getOwnerRecord();
                        $data['geo_country_id'] = $state->geo_country_id;
                        $data['source'] = GeoSource::Manual->value;
                        $data['id'] = ((int) GeoCity::query()->max('id')) + 1;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (GeoCity $record): bool => $record->isDeletable()),
            ])
            ->paginated([25, 50, 100]);
    }
}
