<?php

namespace Miran\Mksine\Filament\Pages;

use Miran\Mksine\Core\Hooks\SettingsTabManager;
use Miran\Mksine\Core\Permalink;
use Miran\Mksine\Models\Page as PageModel;
use Miran\Mksine\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Miran\Mksine\Filament\Forms\Components\MediaPicker;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Actions\Contracts\HasActions;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class Settings extends Page implements HasSchemas, HasActions
{
    use InteractsWithSchemas, InteractsWithActions, HasPageShield;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'mksine::filament.pages.settings';

    protected static ?int $navigationSort = 10;

    public array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('mksine::settings.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('mksine::common.system');
    }

    public function getTitle(): string
    {
        return __('mksine::settings.title');
    }

    public function mount()
    {
        $this->propsMaker($this->form->getFlatFields());

        $this->form->fill($this->data);
    }

    public function form(Schema $form): Schema
    {
        return $form->schema($this->getFormSchema())
            ->inlineLabel()
            ->statePath('data');
    }

    public function getFormSchema(): array
    {
        $coreTabs = $this->getCoreSettingsTabs();
        $extendedTabs = app(SettingsTabManager::class)->getTabs();

        return [
            Tabs::make()
                ->vertical()
                ->tabs(array_merge($coreTabs, $extendedTabs))
                ->persistTabInQueryString(),
        ];
    }

    /**
     * Core Settings tabs (general, permalinks). Extended by plugins via SettingsTabManager.
     *
     * @return array<Tabs\Tab>
     */
    protected function getCoreSettingsTabs(): array
    {
        return [
            Tabs\Tab::make('general')
                ->label(__('mksine::settings.tabs.general'))
                ->schema([
                    MediaPicker::make('logo')
                        ->inlineLabel(false)
                        ->label(__('mksine::settings.logo'))
                        ->isRelation(false)
                        ->collection('logo')
                        ->acceptedFileTypes(['image/*']),

                    MediaPicker::make('favicon')
                        ->inlineLabel(false)
                        ->label(__('mksine::settings.favicon'))
                        ->isRelation(false)
                        ->collection('favicon')
                        ->acceptedFileTypes(['image/*']),

                    TextInput::make('site_name')
                        ->label(__('mksine::settings.site_name'))
                        ->columnSpanFull()
                        ->required(),

                    TextInput::make('short_site_name')
                        ->columnSpanFull()
                        ->label(__('mksine::settings.short_site_name')),
                ])->columns(2),

            Tabs\Tab::make('permalinks')
                ->label(__('mksine::settings.tabs.permalinks'))
                ->schema([
                    Select::make('front_page_id')
                        ->label(__('mksine::settings.front_page'))
                        ->helperText(__('mksine::settings.front_page_helper'))
                        ->placeholder(__('mksine::settings.front_page_default'))
                        ->options(fn () => ['' => __('mksine::settings.front_page_default')] + PageModel::published()->orderBy('title')->pluck('title', 'id')->all())
                        ->searchable()
                        ->allowHtml(false),

                    TextInput::make('home_page_url')
                        ->label(__('mksine::settings.home_page_url'))
                        ->placeholder('/'),

                    TextInput::make('categories_url')
                        ->label(__('mksine::settings.categories_url'))
                        ->placeholder('/categories'),

                    TextInput::make('single_category_url')
                        ->label(__('mksine::settings.single_category_url'))
                        ->placeholder('/category/{path}')
                        ->helperText(__('mksine::settings.single_category_url_helper')),

                    TextInput::make('posts_url')
                        ->label(__('mksine::settings.posts_url'))
                        ->placeholder('/posts'),

                    TextInput::make('single_post_url')
                        ->label(__('mksine::settings.single_post_url'))
                        ->placeholder('/post/{slug}')
                        ->helperText(__('mksine::settings.single_post_url_helper')),

                    TextInput::make('page_url')
                        ->label(__('mksine::settings.page_url'))
                        ->placeholder('/page/{slug}')
                        ->helperText(__('mksine::settings.page_url_helper')),
                ]),
        ];
    }

    protected function propsMaker($items)
    {
        foreach ($items as $key => $item) {
            $this->data[$key] = $this->getSetting($key);
        }
    }

    protected function getSetting(string $key): mixed
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

    public function saveData()
    {
        $this->validate();

        $state = $this->form->getState();
        $permalinkKeys = array_keys(Permalink::getDefaults());

        foreach ($state as $key => $item) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_array($item) ? json_encode($item) : $item]
            );
        }

        if (count(array_intersect($permalinkKeys, array_keys($state))) > 0) {
            \Illuminate\Support\Facades\Artisan::call('route:clear');
        }

        Notification::make()
            ->title(__('mksine::settings.save_success'))
            ->success()
            ->send();
    }

    protected function getActions(): array
    {
        return [
            Action::make('save-data')
                ->label(__('mksine::settings.save'))
                ->action('saveData')
        ];
    }
}

