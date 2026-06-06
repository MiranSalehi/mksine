<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
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
    });
