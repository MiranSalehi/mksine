<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Miran\Mksine\Core\Updater\ArtisanCaller;
use Miran\Mksine\Core\Updater\UpdateException;

it('returns command output when the exit code is zero', function (): void {
    Artisan::command('mks-updater-test:ok', function () {
        $this->line('ok-output');

        return 0;
    });

    expect(ArtisanCaller::callOrFail('mks-updater-test:ok'))->toBe('ok-output');
});

it('throws when the artisan exit code is non-zero', function (): void {
    Artisan::command('mks-updater-test:fail', function () {
        $this->error('migrate exploded');

        return 1;
    });

    expect(fn () => ArtisanCaller::callOrFail('mks-updater-test:fail'))
        ->toThrow(UpdateException::class, 'exit code 1');
});
