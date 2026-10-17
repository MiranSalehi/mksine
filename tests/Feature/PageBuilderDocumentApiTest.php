<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\PageBuilderSection;

uses(RefreshDatabase::class);

it('returns a published builder page as normalized json and hides drafts', function (): void {
    Page::factory()->create([
        'title' => 'Hidden',
        'slug' => 'hidden-draft',
        'type' => 'builder',
        'status' => 'draft',
        'builder_payload' => [
            ['id' => 'secret', 'type' => 'heading', 'data' => ['text' => 'Secret', 'level' => 'h2']],
        ],
    ]);

    Page::factory()->create([
        'title' => 'Home',
        'slug' => 'home-json',
        'type' => 'builder',
        'status' => 'published',
        'published_at' => now()->subMinute(),
        'show_page_header' => false,
        'builder_content_width' => 'full',
        'builder_payload' => [
            ['id' => 'kept', 'type' => 'heading', 'data' => ['text' => 'Hello', 'level' => 'h9']],
            ['id' => 'bad', 'type' => 'not_a_block', 'data' => ['text' => 'nope']],
            ['id' => 'link', 'type' => 'button', 'data' => ['text' => 'Go', 'url' => 'javascript:alert(1)']],
        ],
    ]);

    $this->getJson('/api/mksine/pages/hidden-draft')->assertNotFound();

    $document = $this->getJson('/api/mksine/pages/home-json')
        ->assertOk()
        ->assertJsonPath('schema_version', 1)
        ->assertJsonPath('page.title', 'Home')
        ->assertJsonPath('page.type', 'builder')
        ->assertJsonPath('page.content', null)
        ->assertJsonPath('page.show_page_header', false)
        ->assertJsonPath('page.builder_content_width', 'full')
        ->json();

    expect($document['page']['blocks'])->toHaveCount(2)
        ->and($document['page']['blocks'][0]['type'])->toBe('heading')
        ->and($document['page']['blocks'][0]['data']['level'])->toBe('h2')
        ->and($document['page']['blocks'][0]['presentation'])->toHaveKey('align')
        ->and($document['page']['blocks'][1]['data']['url'])->toBe('#');
});

it('expands a saved section and lists published pages', function (): void {
    $section = PageBuilderSection::query()->create([
        'name' => 'Intro',
        'builder_payload' => [
            ['id' => 'inside', 'type' => 'heading', 'data' => ['text' => 'Inside', 'level' => 'h2']],
        ],
    ]);

    Page::factory()->create([
        'title' => 'Landing',
        'slug' => 'landing-json',
        'type' => 'builder',
        'status' => 'published',
        'published_at' => now()->subMinute(),
        'builder_payload' => [
            ['id' => 'ref', 'type' => 'section_ref', 'data' => ['section_id' => $section->id]],
        ],
    ]);

    $blocks = $this->getJson('/api/mksine/pages/landing-json')->assertOk()->json('page.blocks');

    expect($blocks)->toHaveCount(1)
        ->and($blocks[0]['type'])->toBe('heading')
        ->and($blocks[0]['data']['text'])->toBe('Inside');

    $this->getJson('/api/mksine/pages')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'landing-json')
        ->assertJsonMissing(['slug' => 'hidden-draft']);

    $this->getJson('/api/mksine/pages/blocks')
        ->assertOk()
        ->assertJsonFragment(['type' => 'heading'])
        ->assertJsonFragment(['type' => 'card']);
});
