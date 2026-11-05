<?php

declare(strict_types=1);

use Miran\Mksine\Core\Install\Environment\EnvironmentProbe;
use Miran\Mksine\Core\Install\InstallProfile;

function environmentProbe(string $disabled = '', int $maxExecutionTime = 0): EnvironmentProbe
{
    return new EnvironmentProbe(fn (string $key): string | false => match ($key) {
        'disable_functions' => $disabled,
        'max_execution_time' => (string) $maxExecutionTime,
        'memory_limit' => '256M',
        default => false,
    });
}

it('recommends the VPS profile when processes can run and the web root is public/', function (): void {
    $report = environmentProbe()->probe(__DIR__, __DIR__);

    expect($report->canProcOpen)->toBeTrue()
        ->and($report->publicPathIsDocumentRoot)->toBeTrue()
        ->and($report->recommendedProfile())->toBe(InstallProfile::Vps);
});

it('recommends shared hosting when proc_open is disabled', function (): void {
    $report = environmentProbe('exec, proc_open,symlink', 30)->probe(__DIR__);

    expect($report->canExec)->toBeFalse()
        ->and($report->canSymlink)->toBeFalse()
        ->and($report->hasExecutionTimeLimit())->toBeTrue()
        ->and($report->recommendedProfile())->toBe(InstallProfile::Shared);
});

it('recommends shared hosting when the document root is not the public path', function (): void {
    $report = environmentProbe()->probe(__DIR__, dirname(__DIR__));

    expect($report->publicPathIsDocumentRoot)->toBeFalse()
        ->and($report->recommendedProfile())->toBe(InstallProfile::Shared);
});

it('does not guess the layout on the CLI', function (): void {
    expect(environmentProbe()->probe(__DIR__)->publicPathIsDocumentRoot)->toBeNull();
});
