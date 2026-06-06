<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\Hooks;

use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * Manager for registering tabs on the Settings page.
 * Plugins and app code can add tabs without modifying core.
 *
 * Usage (e.g. in a plugin's boot or AppServiceProvider):
 *
 *   app(SettingsTabManager::class)->registerTab(
 *       id: 'seo',
 *       label: __('SEO'),
 *       schema: [
 *           TextInput::make('meta_description')->label('Meta Description'),
 *       ],
 *       sortOrder: 50
 *   );
 *
 * Field values are stored via Setting::updateOrCreate (same as core tabs).
 */
class SettingsTabManager
{
    /**
     * @var array<int, array{id: string, label: string|\Closure, schema: array|callable, sortOrder: int}>
     */
    private array $tabs = [];

    /**
     * Register a tab on the Settings page.
     *
     * @param  string  $id  Unique tab identifier (e.g. 'my_plugin', 'seo')
     * @param  string|\Closure  $label  Tab label (string or closure for lazy translation — use closure when using __())
     * @param  array|callable  $schema  Form components for the tab, or callable returning array
     * @param  int  $sortOrder  Lower values appear first (default 0)
     */
    public function registerTab(string $id, string|\Closure $label, array|callable $schema, int $sortOrder = 0): void
    {
        foreach ($this->tabs as $existing) {
            if ($existing['id'] === $id) {
                return;
            }
        }

        $this->tabs[] = [
            'id' => $id,
            'label' => $label,
            'schema' => $schema,
            'sortOrder' => $sortOrder,
        ];
    }

    public function hasTabs(): bool
    {
        return $this->tabs !== [];
    }

    /**
     * Build tab instances for the Settings page (core tabs are not included).
     *
     * @return array<Tab>
     */
    public function getTabs(): array
    {
        usort($this->tabs, fn (array $a, array $b): int => $a['sortOrder'] <=> $b['sortOrder']);

        $tabInstances = [];
        foreach ($this->tabs as $def) {
            $schema = is_callable($def['schema'])
                ? $def['schema']()
                : $def['schema'];

            $label = is_callable($def['label']) ? $def['label']() : $def['label'];
            $tabInstances[] = Tab::make($def['id'])
                ->label($label)
                ->schema($schema)
                ->columns(2);
        }

        return $tabInstances;
    }

    /**
     * Clear all registered tabs (mainly for testing).
     */
    public function clear(): void
    {
        $this->tabs = [];
    }
}
