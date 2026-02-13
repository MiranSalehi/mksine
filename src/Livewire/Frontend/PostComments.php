<?php

namespace Miran\Mksine\Livewire\Frontend;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Miran\Mksine\Models\Comment;
use Miran\Mksine\Models\Post;

class PostComments extends Component
{
    public int $postId;

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
            'parent_id' => 'nullable|integer|exists:comments,id',
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

    public function mount(int $postId): void
    {
        $this->postId = $postId;
        if (Auth::check()) {
            $this->author_name = Auth::user()->name ?? '';
            $this->author_email = Auth::user()->email ?? '';
        }
    }

    public function submitComment(): void
    {
        $this->validate();

        $post = Post::findOrFail($this->postId);

        if ($this->parent_id) {
            $parent = Comment::where('id', $this->parent_id)->where('post_id', $post->id)->first();
            if (! $parent) {
                $this->addError('parent_id', __('Invalid reply target.'));
                return;
            }
        }

        Comment::create([
            'post_id' => $post->id,
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

    public function cancelReply(): void
    {
        $this->parent_id = null;
    }

    public function getCommentsProperty()
    {
        return Comment::query()
            ->where('post_id', $this->postId)
            ->approved()
            ->root()
            ->with(['replies' => fn ($q) => $q->approved()->orderBy('created_at')])
            ->orderBy('created_at')
            ->get();
    }

    public function getPostProperty(): ?Post
    {
        return Post::find($this->postId);
    }

    public function render()
    {
        return view('mksine::themes.mksine.partials.post-comments', [
            'comments' => $this->comments,
            'post' => $this->post,
            'parentComment' => $this->parent_id ? Comment::find($this->parent_id) : null,
        ]);
    }
}
