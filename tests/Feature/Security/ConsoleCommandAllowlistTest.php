<?php

declare(strict_types=1);

use Miran\Mksine\Support\Console\AdminConsoleCommandParser;

function parseConsoleCommand(string $input): array
{
    return AdminConsoleCommandParser::parse($input, base_path());
}

it('allows the maintenance commands an operator actually needs', function (string $input): void {
    expect(parseConsoleCommand($input)['argv'])->not->toBeEmpty();
})->with([
    'php artisan optimize:clear',
    'artisan cache:clear',
    'php artisan migrate --force',
    'php artisan queue:restart',
    'php artisan mks:plugin:list',
    'php artisan filament:assets',
    'composer install --no-dev',
    'composer require vendor/package',
]);

it('rejects artisan commands that evaluate arbitrary code', function (string $input): void {
    parseConsoleCommand($input);
})->with([
    'tinker' => 'php artisan tinker',
    'tinker execute' => "php artisan tinker --execute 'echo 1;'",
    'seeder class' => 'php artisan db:seed --class=Whatever',
    'serve' => 'php artisan serve',
    'env' => 'php artisan env',
])->throws(InvalidArgumentException::class);

it('rejects composer sub-commands outside the allowlist', function (string $input): void {
    parseConsoleCommand($input);
})->with([
    'exec' => 'composer exec phpunit',
    'run script' => 'composer run-script post-install-cmd',
    'global' => 'composer global require vendor/package',
])->throws(InvalidArgumentException::class);

it('rejects input that carries options but no sub-command', function (): void {
    parseConsoleCommand('php artisan --version');
})->throws(InvalidArgumentException::class);

it('picks the sub-command past leading options', function (): void {
    expect(fn () => parseConsoleCommand('php artisan --no-interaction tinker'))
        ->toThrow(InvalidArgumentException::class);

    expect(parseConsoleCommand('php artisan --no-interaction optimize:clear')['runner'])->toBe('artisan');
});

it('treats a wildcard allowlist as an explicit opt out', function (): void {
    config()->set('mksine.console_terminal.allowed_commands.artisan', ['*']);

    expect(parseConsoleCommand('php artisan tinker')['runner'])->toBe('artisan');
});

it('denies everything when a runner has an empty allowlist', function (): void {
    config()->set('mksine.console_terminal.allowed_commands.artisan', []);

    parseConsoleCommand('php artisan optimize:clear');
})->throws(InvalidArgumentException::class);

it('keeps the package own commands runnable', function (string $input): void {
    expect(parseConsoleCommand($input)['runner'])->toBe('artisan');
})->with([
    'php artisan migrate:smart',
    'php artisan mks:discover',
    'php artisan mks-plugin:install ecom',
    'php artisan mksine:update',
]);

it('keeps destructive package commands out of the browser', function (): void {
    parseConsoleCommand('php artisan mksine:fresh-super-admin');
})->throws(InvalidArgumentException::class);

it('keeps every shipped daemon preset runnable', function (): void {
    $presets = config('mksine.console_terminal.daemon_presets', []);

    expect($presets)->not->toBeEmpty();

    foreach ($presets as $preset) {
        expect(parseConsoleCommand($preset['command'])['runner'])->toBe('artisan');
    }
});

it('still rejects shell operators and unknown binaries', function (string $input): void {
    parseConsoleCommand($input);
})->with([
    'chaining' => 'php artisan optimize:clear; rm -rf /',
    'substitution' => 'php artisan optimize:clear $(whoami)',
    'unknown binary' => 'curl https://evil.test',
])->throws(InvalidArgumentException::class);
