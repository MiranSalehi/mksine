<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Miran\Mksine\Core\Appearance\Appearance;
use Miran\Mksine\Core\Appearance\AppearanceMode;
use Miran\Mksine\Core\Appearance\AppearancePresets;
use Miran\Mksine\Core\Appearance\AppearanceRail;
use Miran\Mksine\Core\Appearance\AppearanceRenderer;
use Miran\Mksine\Core\Appearance\AppearanceRepository;
use Miran\Mksine\Models\Setting;

uses(RefreshDatabase::class);

function appearanceRepository(): AppearanceRepository
{
    app()->forgetScopedInstances();

    return app(AppearanceRepository::class);
}

test('each part is checked against its allow-list and bad values read as empty', function (): void {
    $look = Appearance::fromArray([
        'preset' => 'classic',
        'primary' => '#0aF',
        'mode' => 'dark',
        'rail' => 'purple',
        'radius' => 'round',
        'font' => 'huge',
        'density' => 'compact',
        'field' => 'floating',
        'label' => 'side',
        'unknown' => 'x',
    ]);

    expect($look->toArray())->toBe([
        'preset' => 'classic',
        'primary' => '#00aaff',
        'mode' => 'dark',
        'radius' => 'round',
        'density' => 'compact',
        'field' => 'floating',
        'label' => 'side',
    ]);

    expect(Appearance::fromArray(['preset' => 'nope', 'primary' => 'blue', 'mode' => ['dark']])->toArray())->toBe([])
        ->and(Appearance::fromArray('garbage')->isEmpty())->toBeTrue()
        ->and(Appearance::fromJson('{not json')->isEmpty())->toBeTrue()
        ->and(Appearance::fromArray(['preset' => 'modern'])->isEmpty())->toBeTrue();
});

test('layers resolve app default, then company, then the person', function (): void {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    $repository = appearanceRepository();

    $repository->saveCompany(Appearance::fromArray(['primary' => '#047857', 'rail' => 'dark']));
    $repository->saveForUser($alice->id, Appearance::fromArray(['primary' => '#047857', 'mode' => 'dark', 'rail' => 'brand']));

    $repository = appearanceRepository();

    // Alice's row keeps only what differs from the company.
    expect($repository->personal($alice->id)->toArray())->toBe(['mode' => 'dark', 'rail' => 'brand'])
        ->and($repository->resolvedFor($alice)->toArray())->toBe(['primary' => '#047857', 'mode' => 'dark', 'rail' => 'brand'])
        ->and($repository->resolvedFor($bob)->toArray())->toBe(['primary' => '#047857', 'rail' => 'dark'])
        ->and($repository->resolvedFor(null)->toArray())->toBe(['primary' => '#047857', 'rail' => 'dark']);

    // A later company change reaches the parts Alice did not touch.
    $repository->saveCompany(Appearance::fromArray(['primary' => '#7c3aed', 'rail' => 'dark']));

    expect(appearanceRepository()->resolvedFor($alice)->primary)->toBe('#7c3aed');
});

test('"same as company" and an empty look remove the row', function (): void {
    $alice = User::factory()->create();
    $repository = appearanceRepository();
    $key = AppearanceRepository::userKey($alice->id);

    $repository->saveForUser($alice->id, Appearance::fromArray(['mode' => 'dark']));
    expect(Setting::query()->where('key', $key)->exists())->toBeTrue();

    $repository->resetUser($alice->id);
    expect(Setting::query()->where('key', $key)->exists())->toBeFalse();

    $repository->saveForUser($alice->id, Appearance::fromArray(['mode' => 'dark']));
    $repository->saveForUser($alice->id, Appearance::fromArray(['preset' => 'modern']));
    expect(Setting::query()->where('key', $key)->exists())->toBeFalse();

    $repository->saveCompany(Appearance::fromArray(['rail' => 'dark']));
    $repository->saveCompany(Appearance::empty());
    expect(Setting::query()->where('key', AppearanceRepository::COMPANY_KEY)->exists())->toBeFalse();
});

test('a person who picks exactly the company look stores nothing', function (): void {
    $alice = User::factory()->create();
    $repository = appearanceRepository();

    $repository->saveCompany(Appearance::fromArray(['rail' => 'light', 'radius' => 'sharp']));
    $repository->saveForUser($alice->id, Appearance::fromArray(['rail' => 'light', 'radius' => 'sharp']));

    expect(Setting::query()->where('key', AppearanceRepository::userKey($alice->id))->exists())->toBeFalse();
});

test('a broken or outdated stored row reads as empty, not as an error', function (): void {
    $alice = User::factory()->create();

    Setting::query()->create(['key' => AppearanceRepository::COMPANY_KEY, 'value' => '{"primary":"#zzz","rail":"neon",']);
    Setting::query()->create(['key' => AppearanceRepository::userKey($alice->id), 'value' => '{"mode":"sepia","radius":"round"}']);
    Setting::query()->create(['key' => AppearanceRepository::SAVED_KEY, 'value' => '[{"name":""},{"name":"Shop","look":{"primary":"#123"}},"x"]']);

    $repository = appearanceRepository();

    expect($repository->company()->isEmpty())->toBeTrue()
        ->and($repository->resolvedFor($alice)->toArray())->toBe(['radius' => 'round'])
        ->and(array_column($repository->saved(), 'name'))->toBe(['Shop'])
        ->and($repository->saved()[0]['appearance']->primary)->toBe('#112233');
});

test('named looks: a repeated name replaces, the 25th and bad names are refused, delete works', function (): void {
    $repository = appearanceRepository();

    $repository->saveNamed('Shop', Appearance::fromArray(['primary' => '#047857']));
    $repository->saveNamed('  shop  ', Appearance::fromArray(['primary' => '#7c3aed']));

    $saved = appearanceRepository()->saved();
    expect($saved)->toHaveCount(1)
        ->and($saved[0]['name'])->toBe('shop')
        ->and($saved[0]['appearance']->primary)->toBe('#7c3aed')
        ->and($saved[0]['appearance']->preset)->toBe('saved:shop');

    for ($i = 2; $i <= AppearanceRepository::MAX_SAVED; $i++) {
        $repository->saveNamed("Look {$i}", Appearance::fromArray(['primary' => '#4f46e5', 'rail' => 'dark']));
    }

    expect(appearanceRepository()->saved())->toHaveCount(AppearanceRepository::MAX_SAVED);

    expect(fn () => appearanceRepository()->saveNamed('One too many', Appearance::fromArray(['rail' => 'dark'])))
        ->toThrow(ValidationException::class);
    expect(fn () => appearanceRepository()->saveNamed('   ', Appearance::fromArray(['rail' => 'dark'])))
        ->toThrow(ValidationException::class);
    expect(fn () => appearanceRepository()->saveNamed(str_repeat('ب', AppearanceRepository::MAX_NAME_LENGTH + 1), Appearance::fromArray(['rail' => 'dark'])))
        ->toThrow(ValidationException::class);

    // A full list still accepts a replacement.
    appearanceRepository()->saveNamed('LOOK 2', Appearance::fromArray(['rail' => 'light']));
    expect(appearanceRepository()->findSaved('look 2')?->rail)->toBe(AppearanceRail::Light);

    appearanceRepository()->deleteNamed('Look 3');
    expect(appearanceRepository()->saved())->toHaveCount(AppearanceRepository::MAX_SAVED - 1)
        ->and(appearanceRepository()->findSaved('Look 3'))->toBeNull();
});

test('a ready look sets colour, sidebar, corners, fields and label but keeps mode, text size and density', function (): void {
    $current = Appearance::fromArray(['primary' => '#047857', 'mode' => 'dark', 'font' => 'large', 'density' => 'compact', 'rail' => 'brand']);

    $classic = AppearancePresets::apply($current, AppearancePresets::preset('classic'), 'classic');
    expect($classic->toArray())->toBe([
        'preset' => 'classic',
        'primary' => '#1e40af',
        'mode' => 'dark',
        'rail' => 'light',
        'radius' => 'sharp',
        'font' => 'large',
        'density' => 'compact',
        'field' => 'outlined',
        'label' => 'side',
    ]);

    $modern = AppearancePresets::apply($classic, AppearancePresets::preset('modern'), 'modern');
    expect($modern->toArray())->toBe(['preset' => 'modern', 'mode' => 'dark', 'font' => 'large', 'density' => 'compact']);

    foreach (array_keys(AppearancePresets::PRESETS) as $key) {
        expect(AppearancePresets::preset($key)->preset)->toBe($key);
    }
});

test('the default look adds nothing to the page head', function (): void {
    expect(app(AppearanceRenderer::class)->renderHead())->toBe('');
});

test('a stored look renders variables and html attributes', function (): void {
    appearanceRepository()->saveCompany(Appearance::fromArray(['primary' => '#047857', 'mode' => 'system', 'rail' => 'dark', 'radius' => 'round']));
    app()->forgetScopedInstances();

    $head = app(AppearanceRenderer::class)->renderHead();

    expect($head)->toContain('--primary-600:#047857;')
        ->toContain('--gray-500:')
        ->toContain('--mks-accent-600:')
        ->toContain('"data-rail":"dark"')
        ->toContain('"data-radius":"round"')
        ->toContain('"mode":"system"')
        ->toContain("localStorage.setItem('theme'");
});

test('a mode-only look sets the mode without any colour variables', function (): void {
    appearanceRepository()->saveCompany(Appearance::fromArray(['mode' => AppearanceMode::Dark->value]));
    app()->forgetScopedInstances();

    $head = app(AppearanceRenderer::class)->renderHead();

    expect($head)->not->toContain('<style')
        ->toContain('"mode":"dark"');
});

test('one person\'s look never reaches another person', function (): void {
    $alice = User::factory()->create();
    $bob = User::factory()->create();
    appearanceRepository()->saveForUser($alice->id, Appearance::fromArray(['primary' => '#a21caf']));

    $this->actingAs($bob);
    app()->forgetScopedInstances();
    expect(app(AppearanceRenderer::class)->renderHead())->toBe('');

    $this->actingAs($alice);
    app()->forgetScopedInstances();
    expect(app(AppearanceRenderer::class)->renderHead())->toContain('--primary-600:#a21caf;');
});
