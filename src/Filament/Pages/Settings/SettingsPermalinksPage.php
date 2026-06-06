<?php

declare(strict_types=1);

namespace Miran\Mksine\Filament\Pages\Settings;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Miran\Mksine\Models\Page as PageModel;

class SettingsPermalinksPage extends MksSettingsPage
{
    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('mksine::settings.tabs.permalinks');
    }

    protected function settingsSchema(): array
    {
        return [
            Select::make('front_page_id')
                ->label(__('mksine::settings.front_page'))
                ->helperText(__('mksine::settings.front_page_helper'))
                ->placeholder(__('mksine::settings.front_page_default'))
                ->options(fn (): array => ['' => __('mksine::settings.front_page_default')] + PageModel::published()->orderBy('title')->pluck('title', 'id')->all())
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
        ];
    }
}
