<?php

declare(strict_types=1);

use Miran\Mksine\Core\Install\Environment\RequirementCheck;
use Miran\Mksine\Core\Install\Environment\RequirementsChecker;

function requirementsChecker(array $missingExtensions = [], array $readOnlyPaths = [], string $php = '8.4.1'): RequirementsChecker
{
    return new RequirementsChecker(
        ['storage' => '/app/storage', 'public' => '/app/public'],
        fn (string $extension): bool => ! in_array($extension, $missingExtensions, true),
        fn (string $path): bool => ! in_array($path, $readOnlyPaths, true),
        $php,
    );
}

it('passes when everything is available', function (): void {
    expect(requirementsChecker()->passes())->toBeTrue();
});

it('blocks on an old PHP version', function (): void {
    $blocking = requirementsChecker(php: '8.1.0')->blocking();

    expect(array_map(fn (RequirementCheck $check): string => $check->key, $blocking))->toBe(['php']);
});

it('blocks on a missing required extension but only warns on a recommended one', function (): void {
    $checker = requirementsChecker(missingExtensions: ['zip', 'intl']);
    $keys = array_map(fn (RequirementCheck $check): string => $check->key, $checker->blocking());

    expect($keys)->toBe(['ext:zip'])
        ->and($checker->passes())->toBeFalse();
});

it('blocks when no PDO driver is loaded', function (): void {
    $checker = requirementsChecker(missingExtensions: RequirementsChecker::DATABASE_DRIVER_EXTENSIONS);

    expect(array_map(fn (RequirementCheck $check): string => $check->key, $checker->blocking()))->toBe(['database-driver']);
});

it('blocks on a read-only path', function (): void {
    $blocking = requirementsChecker(readOnlyPaths: ['/app/public'])->blocking();

    expect($blocking)->toHaveCount(1)
        ->and($blocking[0]->key)->toBe('writable:public')
        ->and($blocking[0]->current)->toBe('/app/public');
});
