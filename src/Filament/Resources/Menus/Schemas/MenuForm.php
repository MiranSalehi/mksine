<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Resources\Menus\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Miran\Mksine\Core\Hooks\FormHookManager;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        $schema = $schema->components([
            TextInput::make('name')
                ->label(__('mksine::menu_builder.name'))
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (string $state, callable $set, ?string $old, $get) {
                    // Auto-generate slug only if slug is empty or matches the old name slug
                    $currentSlug = $get('slug');
                    $oldSlug = $old ? Str::slug($old) : '';

                    if (empty($currentSlug) || $currentSlug === $oldSlug) {
                        $set('slug', Str::slug($state));
                    }
                }),

            TextInput::make('slug')
                ->label(__('mksine::menu_builder.slug'))
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255)
                ->helperText(__('mksine::menu_builder.slug_helper')),

            Textarea::make('description')
                ->label(__('mksine::menu_builder.description'))
                ->rows(3)
                ->maxLength(1000),
        ]);

        // Apply form hooks for extensibility
        $formHookManager = app(FormHookManager::class);

        return $formHookManager->apply('menu.form', $schema);
    }
}
