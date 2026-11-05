<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Miran\Mksine\Core\Install\InstallationState;
use Miran\Mksine\Facades\Mksine;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->originalStoragePath = $this->app->storagePath();
    $this->storagePath = sys_get_temp_dir() . '/mksine-install-state-' . bin2hex(random_bytes(4));
    (new Filesystem)->ensureDirectoryExists($this->storagePath);
    $this->app->useStoragePath($this->storagePath);
});

afterEach(function (): void {
    $this->app->useStoragePath($this->originalStoragePath);
    (new Filesystem)->deleteDirectory($this->storagePath);
});

it('treats a site with core tables and no lock file as a legacy installation', function (): void {
    expect(Schema::hasTable('mks_plugins'))->toBeTrue()
        ->and(app(InstallationState::class)->isInstalled())->toBeTrue()
        ->and(Mksine::isInstalled())->toBeTrue();
});

it('is not installed while an installation is in progress, even when core tables exist', function (): void {
    $state = new InstallationState(new Filesystem);
    (new Filesystem)->ensureDirectoryExists(dirname($state->progressFilePath()));
    file_put_contents($state->progressFilePath(), '{}');

    expect($state->isInstalled())->toBeFalse();
});

it('is not installed when the core tables are missing', function (): void {
    Schema::drop('mks_hooks');

    expect((new InstallationState(new Filesystem))->isInstalled())->toBeFalse();
});

it('writes a lock file with metadata when marked installed', function (): void {
    Schema::drop('mks_hooks');
    $state = new InstallationState(new Filesystem);

    $state->markInstalled(['profile' => 'shared']);

    expect($state->isInstalled())->toBeTrue()
        ->and($state->hasLockFile())->toBeTrue()
        ->and($state->metadata())->toMatchArray(['profile' => 'shared'])
        ->and($state->metadata())->toHaveKeys(['version', 'installed_at']);
});

it('reads the disk again after refresh', function (): void {
    $state = new InstallationState(new Filesystem);
    expect($state->isInstalled())->toBeTrue();

    Schema::drop('mks_hooks');
    expect($state->isInstalled())->toBeTrue();

    $state->refresh();
    expect($state->isInstalled())->toBeFalse();
});
