<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages\Settings\Concerns;

use Miran\Mksine\Core\Appearance\AppearanceRepository;
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

        // Admin looks are written only by their own actions (validated, per-user): a plugin field
        // that happens to use one of these keys must not reach them through this generic form.
        $state = array_filter(
            $this->form->getState(),
            static fn (mixed $item, int|string $key): bool => ! AppearanceRepository::isReservedKey((string) $key),
            ARRAY_FILTER_USE_BOTH,
        );

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
