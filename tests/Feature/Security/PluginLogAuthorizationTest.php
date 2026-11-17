<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Miran\Mksine\Core\Marketplace\DownloadMarketplaceArchive;
use Miran\Mksine\Filament\Pages\ManagePlugins;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->actingAs(User::factory()->create()->assignRole(Role::findOrCreate('ztest-plugin-viewer', 'web')));
});

test('non super admins cannot read or clear plugin logs', function (string $method, array $arguments): void {
    $page = new ManagePlugins;
    $page->pluginLogPluginId = 'ecom';

    expect($page->canManagePluginLogs())->toBeFalse();

    $page->{$method}(...$arguments);
})->with([
    'read' => ['getPluginLogContent', ['ecom']],
    'open' => ['openPluginLog', ['ecom']],
    'clear' => ['clearPluginLog', []],
])->throws(AuthorizationException::class);

test('non super admins cannot make the server download marketplace archives', function (): void {
    app()->bind(DownloadMarketplaceArchive::class, fn () => throw new RuntimeException('The archive must not be downloaded.'));

    (new ManagePlugins)->installFromMarketplace('any-plugin');
})->throws(AuthorizationException::class);
