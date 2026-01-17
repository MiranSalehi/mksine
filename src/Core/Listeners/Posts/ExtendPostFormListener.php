<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Listeners\Posts;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Miran\Mksine\Core\Hooks\FormHookListenerInterface;

/**
 * Example class that extends the Post form with additional fields.
 * This listener will be automatically discovered by mks:discover command.
 */
class ExtendPostFormListener implements FormHookListenerInterface
{
    /**
     * Get the form name this listener extends.
     */
    public static function getFormName(): string
    {
        return 'post.form';
    }

    /**
     * Get the priority for hook execution.
     * Lower numbers execute first (e.g., 0 before 10).
     */
    public static function getPriority(): int
    {
        return 0;
    }

    /**
     * Extend the post form with additional fields.
     * This method is called by FormHookManager.
     *
     * Note: In Filament 4, calling components() will replace existing components.
     * To add components without replacing, you should modify the schema object directly
     * or use a different approach based on Filament 4's API.
     */
    public static function extend(Schema $schema): Schema
    {
        // Get existing components
        $existingComponents = method_exists($schema, 'getComponents')
            ? $schema->getComponents()
            : [];

        // Add new section to existing components
        return $schema->components([
            ...$existingComponents,
            Section::make('Custom Fields')
                ->schema([
                    TextInput::make('custom_field')
                        ->label('Custom Field')
                        ->maxLength(255),
                ])
                ->collapsible()
                ->collapsed(),
        ]);
    }
}
