<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Miran\Mksine\Core\Permalink;
use Miran\Mksine\Core\Theme\ThemeManager;
use Miran\Mksine\Http\Middleware\EnsureActiveThemeDependencies;
use Miran\Mksine\Livewire\Frontend\FrontendResolver;

Route::middleware(['web', EnsureActiveThemeDependencies::class])->group(function () {
    // Load active theme's theme.php (overrides + route callbacks)
    theme_bootstrap();
    // Theme screenshot (served from theme path - no publish required)
    Route::get('/mksine/theme/{identifier}/screenshot', function (string $identifier) {
        $theme = app(ThemeManager::class)->get($identifier);

        if (! $theme?->screenshot) {
            abort(404);
        }

        $path = $theme->path . '/' . $theme->screenshot;

        if (! File::exists($path) || ! is_file($path)) {
            abort(404);
        }

        // Prevent path traversal
        $realPath = realpath($path);
        $realThemePath = realpath($theme->path);
        if (! $realPath || ! $realThemePath || ! str_starts_with($realPath, $realThemePath)) {
            abort(404);
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };

        return response()->file($path, ['Content-Type' => $mime]);
    })->name('mksine.theme.screenshot');

    // Theme custom CSS/JS (admin-edited; served from storage)
    Route::get('/mksine/theme-custom/{identifier}.{type}', function (string $identifier, string $type) {
        if (! in_array($type, ['css', 'js'], true)) {
            abort(404);
        }

        $themeManager = app(ThemeManager::class);
        if (! $themeManager->get($identifier)) {
            abort(404);
        }

        if (! $themeManager->hasCustomAsset($identifier, $type)) {
            abort(404);
        }

        $path = $themeManager->getCustomStoragePath($identifier, $type);
        $content = File::get($path);

        $mime = $type === 'css' ? 'text/css' : 'application/javascript';

        return response($content, 200, [
            'Content-Type' => $mime . '; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    })->where('type', 'css|js')->name('mksine.theme.custom.asset');

    // Frontend pages (resolved via FrontendResolver: theme override or default component)
    Route::get('/author/{id}', FrontendResolver::class)->defaults('page', 'author-show')->name('authors.show');
    Route::get(Permalink::getUri('categories_url'), FrontendResolver::class)->defaults('page', 'category-list')->name('categories.index');
    Route::get(Permalink::getUri('single_category_url'), FrontendResolver::class)->defaults('page', 'category-show')->where('path', '.*')->name('categories.show');
    Route::get(Permalink::getUri('posts_url'), FrontendResolver::class)->defaults('page', 'post-list')->name('posts.index');
    Route::get(Permalink::getUri('single_post_url'), FrontendResolver::class)->defaults('page', 'post-show')->name('posts.show');
    Route::get(Permalink::getUri('page_url'), FrontendResolver::class)->defaults('page', 'page-show')->name('pages.show');
    Route::get(Permalink::getUri('home_page_url'), FrontendResolver::class)->defaults('page', 'home')->name('home');

    // Theme-registered routes (from theme.php theme_register_routes())
    foreach (app(\Miran\Mksine\Core\Theme\ThemeRegistry::class)->getRouteCallbacks() as $callback) {
        $callback();
    }
});
