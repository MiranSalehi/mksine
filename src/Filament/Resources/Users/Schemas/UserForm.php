<?php

namespace Miran\Mksine\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Miran\Mksine\Core\Hooks\FormHookManager;
use Miran\Mksine\Filament\Forms\Components\MediaPicker;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $schema = $schema
            ->components([
                Section::make(__('Profile'))
                    ->schema([
                        MediaPicker::make('avatar')
                            ->label(__('Avatar'))
                            ->collection('avatar')
                            ->acceptedFileTypes(['image/*'])
                            ->isRelation(true)
                            ->maxItems(1)
                            ->columnSpanFull(),

                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label(__('Email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Textarea::make('bio')
                            ->label(__('Bio'))
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        DatePicker::make('date_of_birth')
                            ->label(__('Date of Birth'))
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->maxDate(now()),
                    ])
                    ->columns(2),

                Section::make(__('Contact'))
                    ->schema([
                        Select::make('phone_country_code')
                            ->label(__('Country Code'))
                            ->options(config('mksine.country_dial_codes', []))
                            ->searchable()
                            ->native(false),

                        TextInput::make('phone_number')
                            ->label(__('Phone Number'))
                            ->tel()
                            ->maxLength(20)
                            ->placeholder(__('e.g. 912 123 4567')),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make(__('Security'))
                    ->schema([
                        TextInput::make('password')
                            ->label(__('Password'))
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255),
                        CheckboxList::make('roles')
                            ->relationship('roles', 'name')
                            ->searchable(),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(fn (string $context): bool => $context === 'edit'),
            ]);

        // Apply form hooks
        $formHookManager = app(FormHookManager::class);

        return $formHookManager->apply('user.form', $schema);
    }
}
