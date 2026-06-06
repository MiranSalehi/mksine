<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Miran\Mksine\Http\Controllers\GeoApiController;

Route::middleware(['web'])
    ->prefix('api/geo')
    ->name('mksine.geo.')
    ->group(function (): void {
        Route::get('/countries', [GeoApiController::class, 'countries'])->name('countries');
        Route::get('/states', [GeoApiController::class, 'states'])->name('states');
        Route::get('/cities', [GeoApiController::class, 'cities'])->name('cities');
    });
