<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages\Settings\Concerns;

use Miran\Mksine\Core\Permalink;
use Miran\Mksine\Models\Setting;

trait InteractsWithStoredSettings
{
    public array $data = [];

    protected function loadStoredSettingsIntoForm(): void
    {
        foreach ($this->form->getFlatFields() as $key => $field) {
            $this->data[$key] = $this->readSetting((string) $key);
        }
    }

    protected function readSetting(string $key): mixed
    {
        $item = mks_setting($key);

        return $this->isJson($item) ? json_decode($item, true) : $item;
    }

    protected function isJson(?string $string): bool
    {
        if ($string === null) {
            return false;
        }

        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

    public function saveData(): void
    {
        $this->validate();

        $state = $this->form->getState();

        foreach ($state as $key => $item) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_array($item) ? json_encode($item) : $item],
            );
        }

        $shouldClearRoutes = collect(array_keys($state))->contains(function ($key): bool {
            $k = (string) $key;

            return array_key_exists($k, Permalink::getDefaults())
                || str_starts_with($k, 'ecom_permalink_')
                || $k === 'ecom_storefront_path_prefix'
                || $k === 'ecom_storefront_at_root';
        });

        if ($shouldClearRoutes) {
            \Illuminate\Support\Facades\Artisan::call('route:clear');
        }

        \Filament\Notifications\Notification::make()
            ->title(__('mksine::settings.save_success'))
            ->success()
            ->send();
    }
}
