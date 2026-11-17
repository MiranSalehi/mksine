<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Miran\Mksine\Core\Shortcodes\ShortcodeProcessor;
use Miran\Mksine\Models\Page;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Models\Tag;
use Miran\Mksine\Support\Security\HtmlContentSanitizer;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->author = User::factory()->create();
});

const STORED_XSS_PAYLOAD = '<h2 id="intro">عنوان</h2><p style="text-align:right">متن <strong>پررنگ</strong> <a href="https://example.com" target="_blank" rel="noopener">لینک</a></p>'
    . '<figure class="image"><img src="/storage/a.webp" alt="x" width="400" height="300" onerror="alert(1)"><figcaption>کپشن</figcaption></figure>'
    . '<table><tbody><tr><td colspan="2">خانه</td></tr></tbody></table>'
    . '<script>alert(document.cookie)</script><a href="javascript:alert(1)">بد</a>'
    . '<iframe src="https://www.aparat.com/video/video/embed/videohash/abc/vt/frame" allowfullscreen></iframe>'
    . '<iframe src="https://evil.example/phish"></iframe>';

function sanitizingEditor(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate('ztest-author', 'web'));

    return $user;
}

function sanitizingSuperAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::findOrCreate((string) config('filament-shield.super_admin.name', 'super_admin'), 'web'));

    return $user;
}

/**
 * @param  array<string, mixed>  $data
 * @return array<string, mixed>
 */
function sanitizingBlock(string $type, array $data): array
{
    return ['id' => 'block-' . $type, 'type' => $type, 'data' => $data, 'children' => null];
}

test('script is stripped from post content saved by a non super admin', function (): void {
    $this->actingAs(sanitizingEditor());

    $post = Post::factory()->create(['author_id' => $this->author->id, 'content' => STORED_XSS_PAYLOAD]);
    $stored = (string) $post->fresh()->content;

    expect($stored)
        ->not->toContain('<script')
        ->not->toContain('onerror')
        ->not->toContain('javascript:')
        ->not->toContain('evil.example')
        ->toContain('<h2 id="intro">عنوان</h2>')
        ->toContain('style="text-align:right"')
        ->toContain('target="_blank"')
        ->toContain('<figcaption>کپشن</figcaption>')
        ->toContain('width="400"')
        ->toContain('colspan="2"')
        ->toContain('https://www.aparat.com/video/video/embed/videohash/abc/vt/frame');
});

test('super admins keep raw html', function (): void {
    $this->actingAs(sanitizingSuperAdmin());

    $post = Post::factory()->create(['author_id' => $this->author->id, 'content' => STORED_XSS_PAYLOAD]);

    expect((string) $post->fresh()->content)->toBe(STORED_XSS_PAYLOAD);
});

test('a super admin embed survives when an editor changes another field', function (): void {
    $this->actingAs(sanitizingSuperAdmin());
    $post = Post::factory()->create(['author_id' => $this->author->id, 'content' => '<script src="https://www.aparat.com/embed/x"></script>']);

    $this->actingAs(sanitizingEditor());
    $post->update(['title' => 'عنوان تازه']);

    expect((string) $post->fresh()->content)->toContain('<script');

    $post->update(['content' => $post->content . '<p>more</p>']);

    expect((string) $post->fresh()->content)->not->toContain('<script');
});

test('trusted console runs without a user are not changed', function (): void {
    $post = Post::factory()->create(['author_id' => $this->author->id, 'content' => STORED_XSS_PAYLOAD]);

    expect((string) $post->fresh()->content)->toBe(STORED_XSS_PAYLOAD);
});

test('tag descriptions are sanitised', function (): void {
    $this->actingAs(sanitizingEditor());

    $tag = Tag::query()->create(['name' => 'xss', 'slug' => 'xss', 'description' => '<p>ok</p><img src=x onerror=alert(1)>']);

    expect((string) $tag->fresh()->description)->toContain('<p>ok</p>')->not->toContain('onerror');
});

test('page builder rich text blocks are sanitised and plain fields are left alone', function (): void {
    $payload = [
        sanitizingBlock('text', ['content' => '<p>a</p><script>alert(1)</script>']),
        sanitizingBlock('tabs', ['tabs' => [['title' => 'A & B', 'content' => '<p onclick="x()">t</p>']]]),
        sanitizingBlock('accordion', ['heading' => 'Q & A', 'items' => [['question' => 'q', 'answer' => '<img src=x onerror=alert(1)>']]]),
    ];

    $sanitized = app(HtmlContentSanitizer::class)->sanitizeBuilderPayload($payload);

    expect($sanitized[0]['data']['content'])->toBe('<p>a</p>')
        ->and($sanitized[1]['data']['tabs'][0]['content'])->toBe('<p>t</p>')
        ->and($sanitized[1]['data']['tabs'][0]['title'])->toBe('A & B')
        ->and($sanitized[2]['data']['items'][0]['answer'])->not->toContain('onerror')
        ->and($sanitized[2]['data']['heading'])->toBe('Q & A');

    $nested = app(HtmlContentSanitizer::class)->sanitizeBuilderPayload([
        array_merge(sanitizingBlock('columns', ['columns' => 2]), ['children' => [sanitizingBlock('text', ['content' => '<script>x</script><p>in</p>'])]]),
    ]);

    expect($nested[0]['children'][0]['data']['content'])->toBe('<p>in</p>');
});

test('pages run the builder payload through the sanitiser on save', function (): void {
    $this->actingAs(sanitizingEditor());

    $page = Page::factory()->create([
        'type' => 'builder',
        'content' => '<p>x</p><script>alert(1)</script>',
        'builder_payload' => [sanitizingBlock('text', ['content' => '<p>b</p><script>alert(1)</script>'])],
    ]);

    $fresh = $page->fresh();

    expect((string) $fresh->content)->toBe('<p>x</p>')
        ->and(json_encode($fresh->builder_payload))->not->toContain('script');
});

test('shortcode attributes still parse after sanitising', function (): void {
    $sanitized = (string) app(HtmlContentSanitizer::class)->sanitize('<p>[gallery ids="1,2" title=\'A & B\']</p>');

    $parse = new ReflectionMethod(ShortcodeProcessor::class, 'parseAttributes');

    preg_match('/\[gallery([^\]]*)\]/', $sanitized, $match);

    expect($parse->invoke(null, $match[1]))->toBe(['ids' => '1,2', 'title' => 'A & B'])
        ->and($parse->invoke(null, 'ids="1,2" title="say &quot;hi&quot;"'))->toBe(['ids' => '1,2', 'title' => 'say "hi"']);
});

test('the content scan lists stored script and can clean it', function (): void {
    $dirty = Post::factory()->create(['author_id' => $this->author->id, 'content' => '<p>ok</p><script>alert(1)</script>']);
    Post::factory()->create(['author_id' => $this->author->id, 'content' => '<p>clean</p>']);

    $this->artisan('mksine:content-scan')
        ->expectsOutputToContain('1 record(s) contain markup')
        ->assertFailed();

    $this->artisan('mksine:content-scan', ['--fix' => true])
        ->expectsConfirmation('Sanitise these records now? Embeds and scripts in them will be removed.', 'yes')
        ->assertSuccessful();

    expect((string) $dirty->fresh()->content)->toBe('<p>ok</p>');

    $this->artisan('mksine:content-scan')->assertSuccessful();
});
