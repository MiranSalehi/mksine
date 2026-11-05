<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Miran\Mksine\Support\AdminPanelProviderPatcher;
use Miran\Mksine\Support\WelcomeRoutePatcher;

beforeEach(function (): void {
    $this->dir = sys_get_temp_dir() . '/mksine-patchers-' . bin2hex(random_bytes(4));
    (new Filesystem)->ensureDirectoryExists($this->dir);
});

afterEach(function (): void {
    (new Filesystem)->deleteDirectory($this->dir);
});

const STOCK_ADMIN_PANEL_PROVIDER = <<<'PHP'
<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin');
    }
}
PHP;

it('registers MksinePlugin on a stock admin panel provider and backs it up', function (): void {
    $path = $this->dir . '/AdminPanelProvider.php';
    file_put_contents($path, STOCK_ADMIN_PANEL_PROVIDER);

    $result = (new AdminPanelProviderPatcher($path))->patch();
    $patched = (string) file_get_contents($path);

    expect($result)->toBe(AdminPanelProviderPatcher::RESULT_PATCHED)
        ->and($patched)->toContain('use Miran\Mksine\MksinePlugin;')
        ->and($patched)->toContain("->id('admin')\n            ->plugin(MksinePlugin::make())")
        ->and(glob($path . '.mksine-backup-*'))->toHaveCount(1)
        ->and((new AdminPanelProviderPatcher($path))->patch())->toBe(AdminPanelProviderPatcher::RESULT_ALREADY);
});

it('reports a missing or unrecognised panel provider', function (): void {
    $path = $this->dir . '/AdminPanelProvider.php';

    expect((new AdminPanelProviderPatcher($path))->patch())->toBe(AdminPanelProviderPatcher::RESULT_MISSING);

    file_put_contents($path, "<?php\n\nnamespace App;\n\nclass Other {}\n");

    expect((new AdminPanelProviderPatcher($path))->patch())->toBe(AdminPanelProviderPatcher::RESULT_UNSUPPORTED);
});

it('removes only the stock welcome route', function (string $route): void {
    $path = $this->dir . '/web.php';
    file_put_contents($path, "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n{$route}\n\nRoute::get('/about', fn () => 'about');\n");

    $result = (new WelcomeRoutePatcher($path))->patch();

    expect($result)->toBe(WelcomeRoutePatcher::RESULT_PATCHED)
        ->and((string) file_get_contents($path))->not->toContain('welcome')
        ->and((string) file_get_contents($path))->toContain("Route::get('/about'");
})->with([
    'closure' => "Route::get('/', function () {\n    return view('welcome');\n});",
    'named closure' => "Route::get('/', function () {\n    return view('welcome');\n})->name('home');",
    'view route' => "Route::view('/', 'welcome')->name('home');",
]);

it('leaves a custom home route alone', function (): void {
    $path = $this->dir . '/web.php';
    $contents = "<?php\n\nRoute::get('/', [HomeController::class, 'index']);\n";
    file_put_contents($path, $contents);

    expect((new WelcomeRoutePatcher($path))->patch())->toBe(WelcomeRoutePatcher::RESULT_ABSENT)
        ->and(file_get_contents($path))->toBe($contents);
});
