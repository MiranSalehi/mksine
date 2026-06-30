<?php

namespace Miran\Mksine\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
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
                Section::make(__('mksine::users.section_profile'))
                    ->schema([
                        MediaPicker::make('avatar')
                            ->label(__('mksine::users.avatar'))
                            ->collection('avatar')
                            ->acceptedFileTypes(['image/*'])
                            ->isRelation(true)
                            ->maxItems(1)
                            ->columnSpanFull(),

                        TextInput::make('name')
                            ->label(__('mksine::users.name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label(__('mksine::users.email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Textarea::make('bio')
                            ->label(__('mksine::users.bio'))
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull(),

                        DatePicker::make('date_of_birth')
                            ->label(__('mksine::users.date_of_birth'))
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->maxDate(now()),
                    ])
                    ->inlineLabel()
                    ->columnSpanFull(),

                Section::make(__('mksine::users.section_contact'))
                    ->schema([
                        Select::make('phone_country_code')
                            ->label(__('mksine::users.country_code'))
                            ->options(config('mksine.country_dial_codes', []))
                            ->searchable()
                            ->native(false),

                        TextInput::make('phone_number')
                            ->label(__('mksine::users.phone_number'))
                            ->tel()
                            ->maxLength(20)
                            ->placeholder(__('mksine::users.phone_placeholder')),
                    ])
                    ->columnSpanFull()
                    ->inlineLabel()
                    ->collapsible(),

                Section::make(__('mksine::users.section_security'))
                    ->schema([
                        TextInput::make('password')
                            ->label(__('mksine::users.password'))
                            ->password()
                            ->revealable()
                            ->dehydrated(fn(?string $state): bool => filled($state))
                            ->required(fn(string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->helperText(fn(string $context): ?string => $context === 'edit'
                                ? __('mksine::users.password_leave_blank')
                                : null),
                        CheckboxList::make('roles')
                            ->relationship('roles', 'name')
                            ->searchable(),
                    ])
                    ->columnSpanFull()
                    ->inlineLabel()
                    ->collapsible()
                    ->collapsed(fn(string $context): bool => $context === 'edit'),
            ]);

        // Apply form hooks
        $formHookManager = app(FormHookManager::class);

        return $formHookManager->apply('user.form', $schema);
    }
}
