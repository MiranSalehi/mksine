<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Miran\Mksine\Core\Install\Environment\EnvironmentFileEditor;

beforeEach(function (): void {
    $this->dir = sys_get_temp_dir() . '/mksine-env-' . bin2hex(random_bytes(4));
    mkdir($this->dir);
});

afterEach(function (): void {
    (new Filesystem)->deleteDirectory($this->dir);
});

it('creates .env from .env.example when missing', function (): void {
    file_put_contents($this->dir . '/.env.example', "APP_NAME=Laravel\n# comment\nDB_HOST=127.0.0.1\n");

    $editor = EnvironmentFileEditor::openOrCreate($this->dir . '/.env', $this->dir . '/.env.example');

    expect(file_get_contents($this->dir . '/.env'))->toContain('# comment')
        ->and($editor->get('DB_HOST'))->toBe('127.0.0.1');
});

it('replaces existing keys and appends new ones, keeping comments', function (): void {
    file_put_contents($this->dir . '/.env', "APP_NAME=Laravel\n# keep me\nDB_HOST=127.0.0.1\n");
    $editor = new EnvironmentFileEditor($this->dir . '/.env');

    $editor->set(['DB_HOST' => 'db.example.com', 'QUEUE_CONNECTION' => 'database']);

    expect(file_get_contents($this->dir . '/.env'))
        ->toBe("APP_NAME=Laravel\n# keep me\nDB_HOST=db.example.com\nQUEUE_CONNECTION=database\n");
});

it('quotes values so they read back unchanged', function (string $value): void {
    $editor = EnvironmentFileEditor::openOrCreate($this->dir . '/.env');

    $editor->set(['DB_PASSWORD' => $value]);

    expect($editor->get('DB_PASSWORD'))->toBe($value);
})->with([
    'plain' => 'secret123',
    'spaces' => 'My Site Name',
    'hash and dollar' => 'p#ss$word',
    'single quote' => "it's \"quoted\" \$HOME",
    'empty' => '',
]);

it('formats booleans and null', function (): void {
    expect(EnvironmentFileEditor::formatValue(true))->toBe('true')
        ->and(EnvironmentFileEditor::formatValue(false))->toBe('false')
        ->and(EnvironmentFileEditor::formatValue(null))->toBe('');
});

it('rejects invalid keys and multi-line values', function (): void {
    $editor = EnvironmentFileEditor::openOrCreate($this->dir . '/.env');

    expect(fn () => $editor->set(['bad key' => 'x']))->toThrow(RuntimeException::class)
        ->and(fn () => $editor->set(['GOOD' => "a\nB=c"]))->toThrow(RuntimeException::class);
});

it('generates a base64 application key', function (): void {
    $key = EnvironmentFileEditor::generateAppKey();

    expect($key)->toStartWith('base64:')
        ->and(strlen(base64_decode(substr($key, 7))))->toBe(32);
});
