<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Miran\Mksine\Livewire\Frontend\PostComments;
use Miran\Mksine\Models\Comment;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Support\CommentableType;

uses(RefreshDatabase::class);

/**
 * Same table as Post; only the public-comment gate differs.
 */
final class ClosedForCommentsPost extends Post
{
    protected $table = 'posts';

    public function allowsPublicComments(): bool
    {
        return false;
    }
}

function commentAuthor(): User
{
    return User::factory()->create();
}

function publishedPost(?User $author = null): Post
{
    $author ??= commentAuthor();

    return Post::factory()->published()->forAuthor($author->id)->create();
}

function guestCommentPayload(string $content = 'This is a legitimate public comment.'): array
{
    return [
        'author_name' => 'Ada Lovelace',
        'author_email' => 'ada@example.com',
        'content' => $content,
    ];
}

beforeEach(function (): void {
    RateLimiter::clear(CommentableType::rateLimitKey('127.0.0.1'));
    config()->set('mksine.comments.max_per_minute', 5);
});

it('accepts a guest comment on a post', function (): void {
    $post = publishedPost();

    Livewire::test(PostComments::class, ['postId' => $post->id])
        ->set(guestCommentPayload())
        ->call('submitComment')
        ->assertHasNoErrors();

    expect(Comment::query()->where('commentable_id', $post->id)->count())->toBe(1)
        ->and(Comment::query()->first()?->commentable_type)->toBe($post->getMorphClass())
        ->and(Comment::query()->first()?->status)->toBe(Comment::STATUS_PENDING);
});

it('refuses to let the client retarget the form at a user', function (): void {
    $post = publishedPost();
    $user = commentAuthor();

    Livewire::test(PostComments::class, ['postId' => $post->id])
        ->set('commentableType', User::class)
        ->set('commentableId', $user->id)
        ->set(guestCommentPayload())
        ->call('submitComment');
})->throws(CannotUpdateLockedPropertyException::class);

it('refuses to let the client point the form at a different post', function (): void {
    $post = publishedPost();
    $other = publishedPost();

    Livewire::test(PostComments::class, ['postId' => $post->id])
        ->set('commentableId', $other->id)
        ->set(guestCommentPayload())
        ->call('submitComment');
})->throws(CannotUpdateLockedPropertyException::class);

it('refuses to mount against a type that is not commentable', function (): void {
    $user = commentAuthor();

    Livewire::test(PostComments::class, [
        'commentableType' => User::class,
        'commentableId' => $user->id,
    ]);
})->throws(Illuminate\View\ViewException::class, 'registered commentable type');

it('refuses a record that has closed public comments', function (): void {
    $post = publishedPost();

    config()->set('mksine.commentable_types', [Post::class, ClosedForCommentsPost::class]);

    Livewire::test(PostComments::class, [
        'commentableType' => ClosedForCommentsPost::class,
        'commentableId' => $post->id,
    ])
        ->set(guestCommentPayload())
        ->call('submitComment')
        ->assertHasErrors(['content']);

    expect(Comment::query()->count())->toBe(0);
});

it('rate-limits unauthenticated submissions from the same ip', function (): void {
    $post = publishedPost();
    config()->set('mksine.comments.max_per_minute', 2);

    $component = Livewire::test(PostComments::class, ['postId' => $post->id]);

    $component->set(guestCommentPayload('First allowed comment here.'))->call('submitComment')->assertHasNoErrors();
    $component->set(guestCommentPayload('Second allowed comment here.'))->call('submitComment')->assertHasNoErrors();
    $component->set(guestCommentPayload('This one should be throttled now.'))->call('submitComment')->assertHasErrors(['content']);

    expect(Comment::query()->count())->toBe(2);
});

it('does not count a validation failure against the rate limiter', function (): void {
    $post = publishedPost();
    config()->set('mksine.comments.max_per_minute', 1);

    $component = Livewire::test(PostComments::class, ['postId' => $post->id]);

    $component->set(['author_name' => 'Ada', 'author_email' => 'not-an-email', 'content' => 'Well formed body.'])
        ->call('submitComment')
        ->assertHasErrors(['author_email']);

    $component->set(guestCommentPayload())->call('submitComment')->assertHasNoErrors();

    expect(Comment::query()->count())->toBe(1);
});
