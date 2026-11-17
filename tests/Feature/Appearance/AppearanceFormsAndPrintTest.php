<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Miran\Mksine\Core\Appearance\Appearance;
use Miran\Mksine\Core\Appearance\AppearanceRenderer;
use Miran\Mksine\Core\Appearance\AppearanceRepository;
use Miran\Mksine\Core\Appearance\ColorScale;
use Miran\Mksine\Core\Appearance\PrintColors;
use Miran\Mksine\Filament\Pages\Settings\SettingsAppearancePage;
use Miran\Mksine\Livewire\MyAppearance;
use Miran\Mksine\Models\Setting;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app()->setLocale('en');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function formsAppearanceRepository(): AppearanceRepository
{
    app()->forgetScopedInstances();

    return app(AppearanceRepository::class);
}

function formsSuperAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate((string) config('filament-shield.super_admin.name', 'super_admin'), 'web'));

    return $user;
}

test('field shape and label position reach the html attributes', function (): void {
    formsAppearanceRepository()->saveCompany(Appearance::fromArray(['field' => 'underline', 'label' => 'side']));
    app()->forgetScopedInstances();

    $head = app(AppearanceRenderer::class)->renderHead();

    expect($head)->toContain('"data-field":"underline"')
        ->toContain('"data-label":"side"')
        ->not->toContain('mksFloatingLabels');
});

test('the floating label script ships only with a floating look', function (): void {
    formsAppearanceRepository()->saveCompany(Appearance::fromArray(['field' => 'floating']));
    app()->forgetScopedInstances();

    expect(app(AppearanceRenderer::class)->renderHead())
        ->toContain('"data-field":"floating"')
        ->toContain('window.mksFloatingLabels');
});

test('the look editor keeps a floating field and a side label', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(MyAppearance::class)
        ->call('setPart', 'field', 'floating')
        ->call('setPart', 'label', 'side')
        ->call('setPart', 'field', 'zigzag')
        ->assertSet('look.field', null)
        ->call('setPart', 'field', 'floating')
        ->call('saveAppearance');

    expect(formsAppearanceRepository()->personal($user->id)->toArray())->toBe(['field' => 'floating', 'label' => 'side']);
});

test('prints use the company colour, never a person\'s own look', function (): void {
    $alice = User::factory()->create();
    $repository = formsAppearanceRepository();
    $repository->saveCompany(Appearance::fromArray(['primary' => '#047857']));
    $repository->saveForUser($alice->id, Appearance::fromArray(['primary' => '#a21caf']));

    $this->actingAs($alice);
    app()->forgetScopedInstances();

    $shades = ColorScale::palette('#047857')->shades;
    $css = app(PrintColors::class)->css();

    expect($css)->toContain('--mks-print-title:' . $shades[800] . ';')
        ->toContain('--mks-print-rule:' . $shades[700] . ';')
        ->toContain('--mks-print-head-bg:' . $shades[50] . ';')
        ->toContain('print-color-adjust:exact')
        ->not->toContain(ColorScale::palette('#a21caf')->shades[800]);

    expect(Blade::render('<x-mksine::print.colors />'))->toContain('data-mks-print-colors');
});

test('without a company colour, prints use the panel blue', function (): void {
    expect(app(PrintColors::class)->palette()[800])->toBe(Color::convertToHex(Color::Blue[800]));
});

test('print without colour emits nothing, so print views keep their own black and grey', function (): void {
    formsAppearanceRepository()->saveCompany(Appearance::fromArray(['primary' => '#047857']));
    app()->forgetScopedInstances();

    app(PrintColors::class)->setMonochrome(true);
    app()->forgetScopedInstances();

    expect(app(PrintColors::class)->isMonochrome())->toBeTrue()
        ->and(app(PrintColors::class)->css())->toBe('')
        ->and(trim(Blade::render('<x-mksine::print.colors />')))->toBe('');
});

test('the company page switches print without colour', function (): void {
    $this->actingAs(formsSuperAdmin());

    Livewire::test(SettingsAppearancePage::class)
        ->assertSet('printMonochrome', false)
        ->set('printMonochrome', true)
        ->assertNotified(__('mksine::appearance.print.saved'));

    expect(Setting::query()->where('key', PrintColors::MONOCHROME_KEY)->value('value'))->toBe('1');

    Livewire::test(SettingsAppearancePage::class)->assertSet('printMonochrome', true);
});
