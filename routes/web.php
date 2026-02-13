<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Miran\Mksine\Core\Permalink;
use Miran\Mksine\Core\Theme\ThemeManager;
use Miran\Mksine\Livewire\Frontend\AuthorShow;
use Miran\Mksine\Livewire\Frontend\CategoryList;
use Miran\Mksine\Livewire\Frontend\CategoryShow;
use Miran\Mksine\Livewire\Frontend\Home;
use Miran\Mksine\Livewire\Frontend\PageShow;
use Miran\Mksine\Livewire\Frontend\PostList;
use Miran\Mksine\Livewire\Frontend\PostShow;
use Miran\Mksine\Core\PageBuilder\PagePreviewController;

Route::middleware(['web'])->group(function () {
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

    // Page Builder Preview
    Route::post('/mksine/page-builder/preview', [PagePreviewController::class, 'preview'])
        ->name('mksine.page-builder.preview')
        ->middleware(['auth']);

    // Author (static, not permalink-driven)
    Route::get('/author/{id}', AuthorShow::class)->name('authors.show');

    // Dynamic permalinks from Settings → System → Permalinks
    Route::get(Permalink::getUri('categories_url'), CategoryList::class)->name('categories.index');
    Route::get(Permalink::getUri('single_category_url'), CategoryShow::class)->name('categories.show')->where('path', '.*');
    Route::get(Permalink::getUri('posts_url'), PostList::class)->name('posts.index');
    Route::get(Permalink::getUri('single_post_url'), PostShow::class)->name('posts.show');
    Route::get(Permalink::getUri('page_url'), PageShow::class)->name('pages.show');

    // Home last so it does not catch other paths when set to /
    Route::get(Permalink::getUri('home_page_url'), Home::class)->name('home');
});
