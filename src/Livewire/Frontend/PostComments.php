<?php

declare(strict_types=1);

namespace Miran\Mksine\Livewire\Frontend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Miran\Mksine\Contracts\AllowsPublicComments;
use Miran\Mksine\Models\Comment;
use Miran\Mksine\Models\Post;
use Miran\Mksine\Support\CommentableType;

class PostComments extends Component
{
    #[Locked]
    public string $commentableType;

    #[Locked]
    public int $commentableId;

    /**
     * full: list + form (default). form_only: submission form without duplicating the comment list.
     */
    public string $variant = 'full';

    public string $author_name = '';

    public string $author_email = '';

    public string $content = '';

    /** @var int|null 1-5 */
    public ?int $rating = null;

    public ?int $parent_id = null;

    protected function rules(): array
    {
        $user = Auth::user();
        $nameRequired = $user ? 'nullable' : 'required|string|max:255';
        $emailRequired = $user ? 'nullable' : 'required|email';

        return [
            'author_name' => $nameRequired,
            'author_email' => $emailRequired,
            'content' => 'required|string|min:3|max:5000',
            'rating' => 'nullable|integer|min:1|max:5',
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('comments', 'id')->where(function ($query): void {
                    $query->where('commentable_type', $this->commentableType)
                        ->where('commentable_id', $this->commentableId);
                }),
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'author_name.required' => __('Please enter your name.'),
            'author_email.required' => __('Please enter your email.'),
            'author_email.email' => __('Please enter a valid email address.'),
            'content.required' => __('Please write your comment.'),
            'content.min' => __('Comment must be at least :min characters.'),
        ];
    }

    /**
     * @param  int  $postId  Legacy: same as Post target (use commentable args for new code).
     */
    public function mount(int $postId = 0, string $variant = 'full', ?string $commentableType = null, ?int $commentableId = null): void
    {
        if ($commentableType !== null && $commentableType !== '' && $commentableId !== null && $commentableId > 0) {
            $resolved = CommentableType::resolve($commentableType);

            if ($resolved === null) {
                throw new InvalidArgumentException('PostComments requires a registered commentable type.');
            }

            $this->commentableType = $resolved;
            $this->commentableId = $commentableId;
        } elseif ($postId > 0) {
            $resolved = CommentableType::resolve(Post::class);

            if ($resolved === null) {
                throw new InvalidArgumentException('PostComments requires a registered commentable type.');
            }

            $this->commentableType = $resolved;
            $this->commentableId = $postId;
        } else {
            throw new InvalidArgumentException('PostComments requires postId > 0 or both commentableType and commentableId.');
        }

        $this->variant = in_array($variant, ['full', 'form_only'], true) ? $variant : 'full';
        if (Auth::check()) {
            $this->author_name = Auth::user()->name ?? '';
            $this->author_email = Auth::user()->email ?? '';
        }
    }

    public function submitComment(): void
    {
        $this->validate();

        $commentable = $this->commentable();

        if (! $commentable instanceof AllowsPublicComments) {
            $this->addError('content', __('mksine::frontend.invalid_comment_target'));

            return;
        }

        if (! $commentable->allowsPublicComments()) {
            $this->addError('content', __('mksine::frontend.comments_closed'));

            return;
        }

        if ($this->parent_id) {
            $parent = Comment::query()
                ->where('id', $this->parent_id)
                ->where('commentable_type', $commentable->getMorphClass())
                ->where('commentable_id', $commentable->getKey())
                ->first();
            if (! $parent) {
                $this->addError('parent_id', __('mksine::frontend.invalid_reply_target'));

                return;
            }
        }

        $submitted = RateLimiter::attempt(
            CommentableType::rateLimitKey(),
            CommentableType::maxPerMinute(),
            function () use ($commentable): true {
                Comment::create([
                    'commentable_type' => $commentable->getMorphClass(),
                    'commentable_id' => $commentable->getKey(),
                    'user_id' => Auth::id(),
                    'parent_id' => $this->parent_id ?: null,
                    'author_name' => Auth::check() ? null : $this->author_name,
                    'author_email' => Auth::check() ? null : $this->author_email,
                    'content' => $this->content,
                    'rating' => $this->rating,
                    'status' => Comment::STATUS_PENDING,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);

                return true;
            },
            CommentableType::decaySeconds(),
        );

        if ($submitted === false) {
            $this->addError('content', __('mksine::frontend.comment_too_many'));

            return;
        }

        $this->content = '';
        $this->rating = null;
        $this->parent_id = null;
        $this->dispatch('comment-submitted');
        session()->flash('comment_message', __('Your comment has been submitted and is awaiting moderation.'));
    }

    public function setReply(int $parentId): void
    {
        $this->parent_id = $parentId;
        $this->dispatch('focus-comment-form');
    }

    /**
     * Livewire 4 no longer reliably routes wire:click="$set(...)" as a magic action
     * (it can be treated as a missing public method). Prefer an explicit action.
     */
    public function setRating(int $rating): void
    {
        $this->rating = max(1, min(5, $rating));
    }

    public function cancelReply(): void
    {
        $this->parent_id = null;
    }

    public function getCommentsProperty()
    {
        if ($this->variant === 'form_only') {
            return collect();
        }

        $commentable = $this->commentable();

        if ($commentable === null) {
            return collect();
        }

        return Comment::query()
            ->where('commentable_type', $commentable->getMorphClass())
            ->where('commentable_id', $commentable->getKey())
            ->approved()
            ->root()
            ->with(['replies' => fn ($q) => $q->approved()->orderBy('created_at')])
            ->orderBy('created_at')
            ->get();
    }

    public function getCommentableProperty(): ?Model
    {
        return $this->commentable();
    }

    public function render()
    {
        return view('mksine::themes.mksine.partials.post-comments', [
            'comments' => $this->comments,
            'commentable' => $this->commentable,
            'parentComment' => $this->parentComment(),
            'variant' => $this->variant,
        ]);
    }

    private function commentable(): ?Model
    {
        $class = CommentableType::resolve($this->commentableType);

        if ($class === null) {
            return null;
        }

        return $class::query()->find($this->commentableId);
    }

    private function parentComment(): ?Comment
    {
        if ($this->parent_id === null || $this->parent_id < 1) {
            return null;
        }

        $commentable = $this->commentable();

        if ($commentable === null) {
            return null;
        }

        return Comment::query()
            ->where('id', $this->parent_id)
            ->where('commentable_type', $commentable->getMorphClass())
            ->where('commentable_id', $commentable->getKey())
            ->first();
    }
}
