<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Miran\Mksine\Http\Controllers\PageDocumentController;

Route::middleware(['web', 'throttle:120,1'])
    ->prefix('api/mksine/pages')
    ->name('mksine.pages.api.')
    ->group(function (): void {
        Route::get('/', [PageDocumentController::class, 'index'])->name('index');
        Route::get('/blocks', [PageDocumentController::class, 'catalog'])->name('blocks');
        Route::get('/{slug}', [PageDocumentController::class, 'show'])->where('slug', '[^/]+')->name('show');
    });
