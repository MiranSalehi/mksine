<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Resources\GeoStates\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Miran\Mksine\Enums\GeoSource;
use Miran\Mksine\Models\GeoState;
use Miran\Mksine\Services\Geo\GeoResolver;

final class GeoStateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                Select::make('geo_country_id')
                    ->label(__('mksine::geo.states.country'))
                    ->relationship('country', 'name')
                    ->options(fn (): array => app(GeoResolver::class)->countriesForSelect())
                    ->searchable()
                    ->required()
                    ->disabled(fn (?string $operation): bool => $operation === 'edit'),
                TextInput::make('code')
                    ->label(__('mksine::geo.states.code'))
                    ->maxLength(16),
                TextInput::make('name')
                    ->label(__('mksine::geo.states.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('native')
                    ->label(__('mksine::geo.states.native'))
                    ->maxLength(255),
                Toggle::make('is_visible')
                    ->label(__('mksine::geo.states.is_visible'))
                    ->default(true),
            ])->columns(2),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mutateCreateData(array $data): array
    {
        $data['source'] = GeoSource::Manual->value;
        $data['id'] = ((int) GeoState::query()->max('id')) + 1;

        return $data;
    }
}
