<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Miran\Mksine\Core\PageBuilder\BuilderMediaIndex;
use Miran\Mksine\Core\Plugins\PluginBootGuard;
use Miran\Mksine\Models\Media;
use Miran\Mksine\Models\Menu;
use Miran\Mksine\Models\MenuItem;
use Miran\Mksine\Models\Plugin as PluginModel;
use Miran\Mksine\Models\Setting;
use Miran\Mksine\Support\DatabaseTables;
use Miran\Mksine\Support\MksDateFormatter;
use Miran\Mksine\Support\SettingStore;

uses(RefreshDatabase::class);

/**
 * @return int Number of queries the callback ran.
 */
function countQueries(callable $callback): int
{
    $count = 0;
    DB::listen(function () use (&$count): void {
        $count++;
    });

    $callback();

    return $count;
}

function speedMedia(string $name): Media
{
    return Media::query()->create([
        'name' => $name,
        'file_name' => $name . '.webp',
        'mime_type' => 'image/webp',
        'path' => 'media/' . $name . '.webp',
        'disk' => 'public',
        'size' => 10,
        'url' => '/storage/media/' . $name . '.webp',
    ]);
}

describe('settings', function (): void {
    beforeEach(function (): void {
        Setting::query()->create(['key' => 'speed_a', 'value' => 'one']);
        Setting::query()->create(['key' => MksDateFormatter::SETTING_KEY, 'value' => MksDateFormatter::SHAMSI]);
        app()->forgetScopedInstances();
        Cache::forget(SettingStore::CACHE_KEY);
    });

    test('every setting is read with a single query per request', function (): void {
        $queries = countQueries(function (): void {
            expect(mks_setting('speed_a'))->toBe('one')
                ->and(mks_setting('missing'))->toBeNull();

            for ($i = 0; $i < 50; $i++) {
                MksDateFormatter::formatDateTime(now());
            }
        });

        expect($queries)->toBe(1);
    });

    test('the next request reads settings from the cache', function (): void {
        mks_setting('speed_a');
        app()->forgetScopedInstances();

        expect(countQueries(fn () => mks_setting('speed_a')))->toBe(0);
    });

    test('saving or deleting a setting is visible right away', function (): void {
        expect(mks_setting('speed_a'))->toBe('one');

        Setting::query()->where('key', 'speed_a')->first()->update(['value' => 'two']);
        expect(mks_setting('speed_a'))->toBe('two');

        app()->forgetScopedInstances();
        expect(mks_setting('speed_a'))->toBe('two');

        Setting::query()->where('key', 'speed_a')->first()->delete();
        expect(mks_setting('speed_a'))->toBeNull();
    });
});

test('table checks remember tables that exist but recheck missing ones', function (): void {
    $tables = new DatabaseTables;

    $first = countQueries(fn () => $tables->check('settings'));
    $again = countQueries(fn () => $tables->check('settings'));

    expect($first)->toBe(1)
        ->and($again)->toBe(0)
        ->and($tables->check('speed_missing_table'))->toBeFalse();

    Schema::create('speed_missing_table', fn ($table) => $table->id());

    expect($tables->check('speed_missing_table'))->toBeTrue();

    Schema::drop('speed_missing_table');
});

test('a menu tree is built with one query at any depth and survives parent cycles', function (): void {
    $menu = Menu::query()->create(['name' => 'Main', 'slug' => 'main']);
    $item = fn (string $label, ?int $parentId, int $order): MenuItem => MenuItem::query()->create([
        'menu_id' => $menu->id, 'parent_id' => $parentId, 'type' => MenuItem::TYPE_CUSTOM_LINK,
        'label' => $label, 'url' => '/' . $label, 'order' => $order, 'target' => '_self',
    ]);

    $home = $item('home', null, 0);
    $about = $item('about', null, 1);
    $team = $item('team', $about->id, 0);
    $lead = $item('lead', $team->id, 0);
    $item('deep', $lead->id, 0);
    $loopA = $item('loop-a', null, 2);
    $loopB = $item('loop-b', $loopA->id, 0);
    $loopA->update(['parent_id' => $loopB->id]);

    $tree = [];
    $queries = countQueries(function () use ($menu, &$tree): void {
        $tree = $menu->fresh()->getTree();
    });

    expect($queries)->toBe(2) // the menu itself + all of its items
        ->and(array_column($tree, 'label'))->toBe(['home', 'about'])
        ->and($tree[0]['children'])->toBe([])
        ->and($tree[1]['children'][0]['label'])->toBe('team')
        ->and($tree[1]['children'][0]['children'][0]['children'][0]['label'])->toBe('deep');

    expect($home->id)->toBeInt();
});

test('warming a nested section keeps the outer page media', function (): void {
    $outer = speedMedia('outer');
    $inner = speedMedia('inner');
    $index = new BuilderMediaIndex;

    $index->warm([['type' => 'image', 'data' => ['image' => $outer->id]]]);
    $index->warm([['type' => 'image', 'data' => ['image' => $inner->id]]]);

    $queries = countQueries(function () use ($index, $outer, $inner): void {
        expect($index->find($outer->id)?->id)->toBe($outer->id)
            ->and($index->find($inner->id)?->id)->toBe($inner->id);
    });

    expect($queries)->toBe(0);
});

describe('plugin boot guard', function (): void {
    beforeEach(function (): void {
        $this->guardDir = sys_get_temp_dir() . '/mksine-guard-' . bin2hex(random_bytes(4));
    });

    afterEach(function (): void {
        foreach (glob($this->guardDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->guardDir);
    });

    test('a normal boot touches no files', function (): void {
        $guard = new PluginBootGuard($this->guardDir, 15);

        $guard->startBoot('ecom');
        $guard->endBoot('ecom');
        $guard->recordFatalBootFailure(['type' => E_ERROR, 'message' => 'later', 'file' => 'x', 'line' => 1]);

        expect(glob($this->guardDir . '/*') ?: [])->toBe([]);
    });

    test('a fatal error during boot disables that plugin on the next request', function (): void {
        PluginModel::query()->create(['plugin_id' => 'speed-crash', 'status' => PluginModel::STATUS_ACTIVE]);

        $crashed = new PluginBootGuard($this->guardDir, 15);
        $crashed->startBoot('speed-crash');
        $crashed->recordFatalBootFailure(['type' => E_ERROR, 'message' => 'Allowed memory size exhausted', 'file' => 'x', 'line' => 1]);

        expect(glob($this->guardDir . '/*.booting.json'))->toHaveCount(1);

        (new PluginBootGuard($this->guardDir, 15))->checkPreviousFailures();

        $record = PluginModel::query()->where('plugin_id', 'speed-crash')->first();

        expect($record->status)->toBe(PluginModel::STATUS_INACTIVE)
            ->and($record->boot_failed)->toBeTrue()
            ->and($record->boot_error)->toContain('Allowed memory size exhausted')
            ->and(glob($this->guardDir . '/*.booting.json'))->toBe([]);
    });

    test('warnings during boot are not treated as crashes', function (): void {
        $guard = new PluginBootGuard($this->guardDir, 15);
        $guard->startBoot('ecom');
        $guard->recordFatalBootFailure(['type' => E_WARNING, 'message' => 'notice', 'file' => 'x', 'line' => 1]);

        expect(glob($this->guardDir . '/*') ?: [])->toBe([]);
    });
});

test('the listing index migration adds composite indexes and can be rolled back', function (): void {
    $migration = require dirname(__DIR__, 2) . '/database/migrations/2026_11_17_100000_add_listing_indexes_to_content_tables.php';

    $migration->up();
    $migration->up();

    expect(Schema::hasIndex('posts', ['status', 'published_at']))->toBeTrue()
        ->and(Schema::hasIndex('posts', ['author_id', 'status', 'published_at']))->toBeTrue()
        ->and(Schema::hasIndex('pages', ['status', 'published_at']))->toBeTrue()
        ->and(Schema::hasIndex('categories', ['parent_id', 'is_active', 'sort_order']))->toBeTrue()
        ->and(Schema::hasIndex('taggables', ['tag_id', 'taggable_type', 'taggable_id']))->toBeTrue();

    $migration->down();

    expect(Schema::hasIndex('posts', 'posts_status_published_at_listing_index'))->toBeFalse();
});
