<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Support\CommentableType;

it('resolves a type listed in the commentable allowlist', function (): void {
    expect(CommentableType::resolve(Post::class))->toBe(Post::class)
        ->and(CommentableType::allowed())->toContain(Post::class);
});

it('rejects a model that is not on the allowlist', function (): void {
    expect(CommentableType::resolve(User::class))->toBeNull();
});

it('rejects a string that is not a registered morph alias or class', function (): void {
    expect(CommentableType::resolve('App\\Models\\Order'))->toBeNull()
        ->and(CommentableType::resolve(''))->toBeNull();
});

it('resolves a morph alias when the mapped class is on the allowlist', function (): void {
    $previous = Relation::morphMap();

    try {
        Relation::morphMap(['post' => Post::class], merge: true);

        expect(CommentableType::resolve('post'))->toBe(Post::class);
    } finally {
        Relation::morphMap($previous ?: [], merge: false);
    }
});

it('does not treat an allowlisted class that skipped AllowsPublicComments as commentable', function (): void {
    config()->set('mksine.commentable_types', [User::class, Post::class]);

    expect(CommentableType::resolve(User::class))->toBeNull()
        ->and(CommentableType::allowed())->toBe([Post::class]);
});
