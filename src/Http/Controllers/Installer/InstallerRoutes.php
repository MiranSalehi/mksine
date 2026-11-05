<?php

declare(strict_types=1);

namespace Miran\Mksine\Http\Controllers\Installer;

use Illuminate\Support\Facades\Route;
use Miran\Mksine\Http\Middleware\Installer\PrepareInstallerRequest;
use Miran\Mksine\Http\Middleware\Installer\RedirectToInstaller;

/**
 * Web installer routes. Registered only while MKSine is not installed.
 */
final class InstallerRoutes
{
    public static function register(): void
    {
        Route::middleware(['web', PrepareInstallerRequest::class])
            ->prefix(RedirectToInstaller::installerPath())
            ->name('mksine.installer.')
            ->controller(InstallerController::class)
            ->group(function (): void {
                Route::get('/', 'welcome')->name('welcome');
                Route::post('/language', 'storeLanguage')->name('language');
                Route::get('/verify', 'verify')->name('verify');
                Route::post('/verify', 'storeVerify')->middleware('throttle:10,1')->name('verify.store');
                Route::get('/requirements', 'requirements')->name('requirements');
                Route::get('/database', 'database')->name('database');
                Route::post('/database', 'storeDatabase')->middleware('throttle:10,1')->name('database.store');
                Route::get('/site', 'site')->name('site');
                Route::post('/site', 'storeSite')->name('site.store');
                Route::get('/run', 'run')->name('run');
                Route::post('/run', 'runNext')->name('run.next');
            });
    }
}
