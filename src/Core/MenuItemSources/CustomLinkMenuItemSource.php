<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\MenuItemSources;

use Miran\Mksine\Contracts\MenuItemSourceInterface;
use Miran\Mksine\Models\MenuItem;

/**
 * Custom Link item source for Menu Builder.
 *
 * Allows adding arbitrary URLs with custom labels.
 */
class CustomLinkMenuItemSource implements MenuItemSourceInterface
{
    public function getKey(): string
    {
        return 'custom_link';
    }

    public function getLabel(): string
    {
        return (string) __('mksine::menu_builder.custom_link');
    }

    public function getIcon(): string
    {
        return 'heroicon-o-link';
    }

    /**
     * Custom links don't have a list of items.
     */
    public function getItems(): array
    {
        return [];
    }

    public function toMenuItem(mixed $item): array
    {
        return [
            'type' => MenuItem::TYPE_CUSTOM_LINK,
            'label' => $item['label'] ?? '',
            'url' => $item['url'] ?? '',
            'reference_id' => null,
        ];
    }

    /**
     * Custom link uses a form instead of checkbox list.
     */
    public function getFormSchema(): ?array
    {
        return [
            \Filament\Forms\Components\TextInput::make('url')
                ->label(__('URL'))
                ->url()
                ->required()
                ->placeholder('https://'),
            \Filament\Forms\Components\TextInput::make('label')
                ->label(__('Link Text'))
                ->required()
                ->placeholder(__('Enter link text')),
        ];
    }

    public function supportsMultipleSelection(): bool
    {
        return false;
    }
}
