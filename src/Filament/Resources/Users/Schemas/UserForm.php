<?php

namespace Miran\Mksine\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Miran\Mksine\Core\Hooks\FormHookManager;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $schema = $schema
            ->components([
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

                TextInput::make('password')
                    ->label(__('Password'))
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255),
            ]);

        // Apply form hooks
        $formHookManager = app(FormHookManager::class);

        return $formHookManager->apply('user.form', $schema);
    }
}
