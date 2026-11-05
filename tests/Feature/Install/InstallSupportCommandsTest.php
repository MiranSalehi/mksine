<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('reports requirements, environment and install state', function (): void {
    $exit = Artisan::call('mksine:doctor');
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('Requirements')
        ->and($output)->toContain('Recommended profile')
        ->and($output)->toContain('Installed');
});

it('prints an nginx config rooted at the public path', function (): void {
    $exit = Artisan::call('mksine:server-config', ['type' => 'nginx', '--domain' => 'shop.example.com']);
    $output = Artisan::output();

    expect($exit)->toBe(0)
        ->and($output)->toContain('server_name shop.example.com;')
        ->and($output)->toContain('root ' . public_path() . ';')
        ->and($output)->toContain('try_files $uri $uri/ /index.php?$query_string;');
});

it('prints every config type', function (): void {
    Artisan::call('mksine:server-config');
    $output = Artisan::output();

    expect($output)->toContain('# --- nginx ---')
        ->and($output)->toContain('<VirtualHost *:80>')
        ->and($output)->toContain('[program:mksine-worker]')
        ->and($output)->toContain('schedule:run');
});

it('rejects an unknown config type', function (): void {
    expect(Artisan::call('mksine:server-config', ['type' => 'iis']))->toBe(1);
});
