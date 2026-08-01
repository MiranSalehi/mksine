<?php

namespace Miran\Mksine\Livewire\Frontend;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Miran\Mksine\Contracts\AllowsPublicComments;
use Miran\Mksine\Models\Comment;
use Miran\Mksine\Models\Post;

class PostComments extends Component
{
    public string $commentableType;

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
     * @param  int  $postId  Legacy: same as Post target (use {@see Post::class} + id via commentable args for new code).
     */
    public function mount(int $postId = 0, string $variant = 'full', ?string $commentableType = null, ?int $commentableId = null): void
    {
        if ($commentableType !== null && $commentableType !== '' && $commentableId !== null && $commentableId > 0) {
            $this->commentableType = $commentableType;
            $this->commentableId = $commentableId;
        } elseif ($postId > 0) {
            $this->commentableType = Post::class;
            $this->commentableId = $postId;
        } else {
            throw new \InvalidArgumentException('PostComments requires postId > 0 or both commentableType and commentableId.');
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

        if (! is_subclass_of($this->commentableType, Model::class) || ! class_exists($this->commentableType)) {
            $this->addError('content', __('Invalid comment target.'));

            return;
        }

        /** @var class-string<Model> $class */
        $class = $this->commentableType;
        $commentable = $class::query()->findOrFail($this->commentableId);

        if ($commentable instanceof AllowsPublicComments && ! $commentable->allowsPublicComments()) {
            $this->addError('content', __('Comments are closed for this item.'));

            return;
        }

        if ($this->parent_id) {
            $parent = Comment::query()
                ->where('id', $this->parent_id)
                ->where('commentable_type', $this->commentableType)
                ->where('commentable_id', $this->commentableId)
                ->first();
            if (! $parent) {
                $this->addError('parent_id', __('Invalid reply target.'));

                return;
            }
        }

        Comment::create([
            'commentable_type' => $this->commentableType,
            'commentable_id' => $this->commentableId,
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

        return Comment::query()
            ->where('commentable_type', $this->commentableType)
            ->where('commentable_id', $this->commentableId)
            ->approved()
            ->root()
            ->with(['replies' => fn ($q) => $q->approved()->orderBy('created_at')])
            ->orderBy('created_at')
            ->get();
    }

    public function getCommentableProperty(): ?Model
    {
        if (! is_subclass_of($this->commentableType, Model::class) || ! class_exists($this->commentableType)) {
            return null;
        }

        /** @var class-string<Model> $class */
        $class = $this->commentableType;

        return $class::query()->find($this->commentableId);
    }

    public function render()
    {
        return view('mksine::themes.mksine.partials.post-comments', [
            'comments' => $this->comments,
            'commentable' => $this->commentable,
            'parentComment' => $this->parent_id ? Comment::find($this->parent_id) : null,
            'variant' => $this->variant,
        ]);
    }
}
