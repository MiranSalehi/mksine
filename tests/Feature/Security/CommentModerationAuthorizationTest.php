<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Miran\Mksine\Filament\Resources\Comments\Pages\ListComments;
use Miran\Mksine\Models\Comment;
use Miran\Mksine\Models\Post;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

/**
 * @param  list<string>  $permissions
 */
function commentModeratorWith(array $permissions): User
{
    $role = Role::findOrCreate('ztest-comments-' . md5(implode(',', $permissions)), 'web');

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $role->syncPermissions($permissions);

    $user = User::factory()->create();
    $user->assignRole($role);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

function pendingCommentOnPost(): Comment
{
    $post = Post::query()->create([
        'title' => 'Commented',
        'slug' => 'commented-' . uniqid(),
        'content' => '<p>Body</p>',
        'status' => 'published',
        'author_id' => User::factory()->create()->getKey(),
    ]);

    return Comment::query()->create([
        'commentable_type' => $post->getMorphClass(),
        'commentable_id' => $post->getKey(),
        'author_name' => 'Guest',
        'author_email' => 'guest@example.test',
        'content' => 'Hello',
        'status' => Comment::STATUS_PENDING,
    ]);
}

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('view only moderators cannot approve, spam, trash or reply', function (): void {
    $comment = pendingCommentOnPost();
    $this->actingAs(commentModeratorWith(['ViewAny:Comment', 'View:Comment']));

    Livewire::test(ListComments::class)
        ->assertActionHidden(TestAction::make('approve')->table($comment))
        ->assertActionHidden(TestAction::make('reply')->table($comment))
        ->assertActionHidden(TestAction::make('spam')->table($comment))
        ->assertActionHidden(TestAction::make('trash')->table($comment))
        ->assertActionHidden(TestAction::make('approve')->table()->bulk())
        ->assertActionHidden(TestAction::make('spam')->table()->bulk())
        ->assertActionHidden(TestAction::make('trash')->table()->bulk());

    expect($comment->refresh()->status)->toBe(Comment::STATUS_PENDING)
        ->and(Comment::query()->count())->toBe(1);
});

test('moderators with update comment can approve and bulk spam', function (): void {
    $comment = pendingCommentOnPost();
    $this->actingAs(commentModeratorWith(['ViewAny:Comment', 'View:Comment', 'Update:Comment']));

    Livewire::test(ListComments::class)
        ->callAction(TestAction::make('approve')->table($comment))
        ->assertHasNoErrors();

    expect($comment->refresh()->status)->toBe(Comment::STATUS_APPROVED);

    // The list opens on the pending tab, so bulk-moderate another pending comment.
    $comment = pendingCommentOnPost();

    Livewire::test(ListComments::class)
        ->assertActionVisible(TestAction::make('spam')->table()->bulk())
        ->callTableBulkAction('spam', [$comment])
        ->assertNotified(__('mksine::comments.comments_marked_spam', ['count' => 1]));

    expect($comment->refresh()->status)->toBe(Comment::STATUS_SPAM);
});

test('replying needs create comment', function (): void {
    $comment = pendingCommentOnPost();
    $this->actingAs(commentModeratorWith(['ViewAny:Comment', 'View:Comment', 'Create:Comment']));

    Livewire::test(ListComments::class)
        ->callAction(TestAction::make('reply')->table($comment), data: ['content' => 'Thanks'])
        ->assertHasNoErrors();

    expect(Comment::query()->where('parent_id', $comment->getKey())->value('content'))->toBe('Thanks');
});
