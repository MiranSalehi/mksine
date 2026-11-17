<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Miran\Mksine\Models\Setting;
use Miran\Mksine\Support\MksDateFormatter;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['app.timezone' => 'Asia/Tehran']);

    Setting::query()->updateOrCreate(
        ['key' => MksDateFormatter::SETTING_KEY],
        ['value' => MksDateFormatter::SHAMSI],
    );
});

/**
 * ICU 67 (production) rejects "+03:30" as a time zone name while ICU 76 (macOS) accepts it,
 * so these tests check the name ICU would receive instead of relying on ICU to fail.
 */
dataset('non identifier time zones', [
    'positive offset' => ['2026-11-16T13:27:00+03:30'],
    'negative offset' => ['2026-11-16T05:57:00-05:00'],
    'zulu suffix' => ['2026-11-16T09:57:00Z'],
    'abbreviation' => ['2026-11-16 04:57:00 EST'],
]);

test('offset time zones never reach the intl formatter', function (string $input): void {
    $date = Carbon::parse($input);

    $prepared = MksDateFormatter::forIntlFormatter($date);
    $name = $prepared->getTimezone()->getName();

    expect($name)->not->toStartWith('+')
        ->and($name)->not->toStartWith('-')
        ->and($prepared->getTimezone()->__serialize()['timezone_type'])->toBe(3)
        ->and($name)->toBe('Asia/Tehran')
        ->and($prepared->getTimestamp())->toBe($date->getTimestamp())
        ->and($date->getTimezone()->getName())->toBe(Carbon::parse($input)->getTimezone()->getName());
})->with('non identifier time zones');

test('identifier time zones are passed through unchanged', function (): void {
    $date = Carbon::parse('2026-11-16 13:27:00', 'Europe/Berlin');

    expect(MksDateFormatter::forIntlFormatter($date))->toBe($date)
        ->and($date->getTimezone()->getName())->toBe('Europe/Berlin');
});

test('falls back to utc when the app time zone is itself an offset', function (): void {
    config(['app.timezone' => '+03:30']);

    $prepared = MksDateFormatter::forIntlFormatter(Carbon::parse('2026-11-16T13:27:00+03:30'));

    expect($prepared->getTimezone()->getName())->toBe('UTC')
        ->and($prepared->format('H:i'))->toBe('09:57');
});

test('shamsi formatting of an iso string with offset matches the app time zone rendering', function (): void {
    $fromOffset = MksDateFormatter::formatDateTime('2026-11-16T13:27:00+03:30', 'yyyy/MM/dd HH:mm');
    $fromIdentifier = MksDateFormatter::formatDateTime(
        Carbon::parse('2026-11-16 13:27:00', 'Asia/Tehran'),
        'yyyy/MM/dd HH:mm',
    );

    expect($fromOffset)->toBe($fromIdentifier)->not->toBeNull();
})->skip(! extension_loaded('intl'), 'intl extension is not loaded');
