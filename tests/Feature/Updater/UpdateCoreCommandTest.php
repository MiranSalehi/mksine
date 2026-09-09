<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Miran\Mksine\Console\Commands\UpdateCoreCommand;
use Miran\Mksine\Support\ComposerBinary;

afterEach(function (): void {
    ComposerBinary::clearFake();
});

it('fails with an offline playbook when composer is not available', function (): void {
    ComposerBinary::fake(null);

    $code = Artisan::call('mksine:update', ['--force' => true]);
    $output = Artisan::output();

    expect($code)->toBe(1);
    expect($output)->toContain('Composer is not available');
    expect($output)->toContain('mks:release-archive');
    expect($output)->toContain('lockfile');
});

it('does not accept a ZIP file argument', function (): void {
    $definition = $this->app->make(UpdateCoreCommand::class)->getDefinition();

    expect($definition->hasArgument('file'))->toBeFalse();
    expect($definition->hasArgument('zip'))->toBeFalse();
});
