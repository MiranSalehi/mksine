<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Miran\Mksine\Filament\Pages\MenuBuilder;
use Miran\Mksine\Models\Menu;
use Miran\Mksine\Models\MenuItem;
use Miran\Mksine\Services\MenuService;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function menuWithItems(int $count): Menu
{
    $menu = Menu::query()->create(['name' => 'Menu', 'slug' => 'menu-' . uniqid()]);

    for ($i = 0; $i < $count; $i++) {
        MenuItem::query()->create([
            'menu_id' => $menu->id,
            'type' => MenuItem::TYPE_CUSTOM_LINK,
            'label' => 'Item ' . $i,
            'url' => '/item-' . $i,
            'order' => $i,
            'target' => '_self',
        ]);
    }

    return $menu;
}

test('menu link schemes are checked after browser normalisation', function (string $url, bool $safe): void {
    expect(MenuItem::isSafeUrl($url))->toBe($safe);
})->with([
    'https' => ['https://example.test/a', true],
    'mailto' => ['mailto:shop@example.test', true],
    'tel' => ['tel:+989120000000', true],
    'site path' => ['/shop', true],
    'relative' => ['about-us', true],
    'anchor' => ['#top', true],
    'javascript' => ['javascript:alert(1)', false],
    'uppercase javascript' => ['JavaScript:alert(1)', false],
    'tab inside scheme' => ["java\tscript:alert(1)", false],
    'leading space' => ['  javascript:alert(1)', false],
    'entity encoded colon' => ['javascript&colon;alert(1)', false],
    'data uri' => ['data:text/html;base64,PHNjcmlwdD4=', false],
    'vbscript' => ['vbscript:msgbox(1)', false],
]);

test('unsafe links are neutralised on save and on render', function (): void {
    $menu = menuWithItems(0);

    $item = MenuItem::query()->create([
        'menu_id' => $menu->id,
        'type' => MenuItem::TYPE_CUSTOM_LINK,
        'label' => 'Bad',
        'url' => 'javascript:alert(document.cookie)',
        'order' => 0,
        'target' => '_self',
    ]);

    expect($item->refresh()->url)->toBe('#');

    // A row written before this release, straight to the table.
    MenuItem::query()->whereKey($item->id)->toBase()->update(['url' => 'javascript:alert(1)']);

    expect($menu->getTree()[0]['url'])->toBe('#')
        ->and(app(MenuService::class)->buildTree($menu->refresh())['items'][0]['url'])->toBe('#');
});

describe('menu builder', function (): void {
    beforeEach(function (): void {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->create()->assignRole(
            Role::findOrCreate(config('filament-shield.super_admin.name', 'super_admin'), 'web'),
        ));
    });

    test('custom links with an unsafe scheme are refused', function (): void {
        $menu = menuWithItems(0);

        Livewire::test(MenuBuilder::class, ['menu' => $menu->id])
            ->set('sourceFormData.custom_link', ['url' => 'javascript:alert(1)', 'label' => 'x'])
            ->call('addItemFromSource', 'custom_link')
            ->assertNotified(__('mksine::menu_builder.unsafe_url'));

        expect($menu->items()->count())->toBe(0);
    });

    test('structure updates ignore items of other menus and repeated ids', function (): void {
        $menu = menuWithItems(2);
        $other = menuWithItems(1);
        [$first, $second] = $menu->items()->orderBy('order')->pluck('id')->all();
        $foreign = (int) $other->items()->value('id');

        Livewire::test(MenuBuilder::class, ['menu' => $menu->id])
            ->call('updateMenuStructure', [
                ['id' => $first, 'children' => [
                    ['id' => $second, 'children' => [
                        ['id' => $first, 'children' => []],
                    ]],
                    ['id' => $foreign, 'children' => []],
                ]],
            ]);

        expect(MenuItem::query()->find($first)->parent_id)->toBeNull()
            ->and(MenuItem::query()->find($second)->parent_id)->toBe($first)
            ->and(MenuItem::query()->find($foreign)->parent_id)->toBeNull()
            ->and(MenuItem::query()->find($foreign)->menu_id)->toBe($other->id);
    });

    test('items of another menu cannot be removed or edited', function (): void {
        $menu = menuWithItems(1);
        $foreign = (int) menuWithItems(1)->items()->value('id');

        Livewire::test(MenuBuilder::class, ['menu' => $menu->id])
            ->call('removeItem', $foreign);

        expect(MenuItem::query()->whereKey($foreign)->exists())->toBeTrue();
    });

    test('the item tree cannot be replaced from the browser', function (): void {
        $menu = menuWithItems(1);

        Livewire::test(MenuBuilder::class, ['menu' => $menu->id])
            ->set('menuItems', [['id' => 999, 'children' => []]]);
    })->throws(CannotUpdateLockedPropertyException::class);
});
