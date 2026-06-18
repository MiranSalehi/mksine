<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Miran\Mksine\Http\Controllers\ConsoleInteractiveController;
use Miran\Mksine\Http\Controllers\ConsoleProcessController;
use Miran\Mksine\Http\Controllers\ConsoleProcessStreamController;

$prefix = trim((string) config('mksine.console_terminal.api_prefix', 'admin/mksine/console'), '/');

Route::middleware(['web', 'auth'])
    ->prefix($prefix)
    ->name('mksine.console-process.')
    ->group(function (): void {
        Route::post('start', [ConsoleProcessController::class, 'start'])->name('start');
        Route::get('active', [ConsoleProcessController::class, 'active'])->name('active');
        Route::post('{process}/stop', [ConsoleProcessController::class, 'stop'])->name('stop');
        Route::get('{process}/status', [ConsoleProcessController::class, 'status'])->name('status');
        Route::get('{process}/output', [ConsoleProcessController::class, 'output'])->name('output');
        Route::get('{process}/stream', ConsoleProcessStreamController::class)->name('stream');

        Route::prefix('interactive')->name('interactive.')->group(function (): void {
            Route::post('detect', [ConsoleInteractiveController::class, 'detect'])->name('detect');
            Route::post('log', [ConsoleInteractiveController::class, 'storeLog'])->name('log');
            Route::get('migrate-smart/catalog', [ConsoleInteractiveController::class, 'migrateSmartCatalog'])->name('migrate-smart.catalog');
            Route::post('migrate-smart/analyze', [ConsoleInteractiveController::class, 'migrateSmartAnalyze'])->name('migrate-smart.analyze');
            Route::post('migrate-smart/execute', [ConsoleInteractiveController::class, 'migrateSmartExecute'])->name('migrate-smart.execute');
        });
    });
