<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Miran\Mksine\Core\PageBuilder\PageBuilderRevisionStore;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\PageBuilderRevision;

uses(RefreshDatabase::class);

it('records a changed builder payload and ignores an identical save', function (): void {
    $page = Page::factory()->create([
        'type' => 'builder',
        'status' => 'draft',
        'slug' => 'revision-home',
        'builder_payload' => [
            ['id' => 'block_a', 'type' => 'heading', 'data' => ['text' => 'One']],
        ],
    ]);

    $store = app(PageBuilderRevisionStore::class);
    $store->record($page);
    $store->record($page->fresh());

    expect(PageBuilderRevision::query()->where('page_id', $page->id)->count())->toBe(1);

    $page->builder_payload = [
        ['id' => 'block_a', 'type' => 'heading', 'data' => ['text' => 'Two']],
    ];
    $page->save();
    $store->record($page->fresh());

    expect(PageBuilderRevision::query()->where('page_id', $page->id)->count())->toBe(2);
});

it('does not record a simple page', function (): void {
    $page = Page::factory()->create([
        'type' => 'simple',
        'status' => 'draft',
        'slug' => 'revision-simple',
        'builder_payload' => null,
        'content' => '<p>Hello</p>',
    ]);

    app(PageBuilderRevisionStore::class)->record($page);

    expect(PageBuilderRevision::query()->count())->toBe(0);
});

it('restores an older payload and keeps that restore as the newest revision', function (): void {
    $original = [
        ['id' => 'block_a', 'type' => 'heading', 'data' => ['text' => 'One']],
    ];
    $edited = [
        ['id' => 'block_a', 'type' => 'heading', 'data' => ['text' => 'Two']],
    ];

    $page = Page::factory()->create([
        'type' => 'builder',
        'status' => 'draft',
        'slug' => 'revision-restore',
        'builder_payload' => $original,
    ]);

    $store = app(PageBuilderRevisionStore::class);
    $store->record($page);

    $page->builder_payload = $edited;
    $page->save();
    $store->record($page->fresh());

    $first = PageBuilderRevision::query()->where('page_id', $page->id)->oldest('id')->first();
    expect($first)->not->toBeNull();

    $store->restore($page->fresh(), (int) $first->getKey());

    $page->refresh();
    expect($page->builder_payload)->toEqual($original)
        ->and(PageBuilderRevision::query()->where('page_id', $page->id)->count())->toBe(3);
});

it('keeps only the newest twenty revisions', function (): void {
    $page = Page::factory()->create([
        'type' => 'builder',
        'status' => 'draft',
        'slug' => 'revision-cap',
        'builder_payload' => [],
    ]);

    $store = app(PageBuilderRevisionStore::class);

    for ($i = 0; $i < 25; $i++) {
        $page->builder_payload = [
            ['id' => 'block_a', 'type' => 'heading', 'data' => ['text' => 'Version '.$i]],
        ];
        $page->save();
        $store->record($page->fresh());
    }

    expect(PageBuilderRevision::query()->where('page_id', $page->id)->count())->toBe(PageBuilderRevisionStore::KEEP);

    $oldest = PageBuilderRevision::query()->where('page_id', $page->id)->oldest('id')->first();
    expect($oldest?->builder_payload[0]['data']['text'] ?? null)->toBe('Version 5');
});
