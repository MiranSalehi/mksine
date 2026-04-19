@props(['comment', 'isReply' => false])

<div
    class="{{ $isReply ? 'ms-4 mt-4 border-s-2 border-violet-200/80 ps-4 first:pt-0 pt-4 dark:border-violet-800/60 sm:ms-8 sm:ps-5' : 'first:pt-0 pt-6' }} border-b border-stone-100 pb-6 last:mb-0 last:border-b-0 last:pb-0 dark:border-slate-800"
    @if (! $isReply) id="comment-{{ $comment->id }}" @endif
>
    <div class="mb-3 flex gap-3 sm:gap-4">
        @php
            $user = $comment->user;
            $avatarUrl = $user && method_exists($user, 'avatar_url') ? $user->avatar_url : null;
            $initials = $user && method_exists($user, 'initials') ? $user->initials() : strtoupper(mb_substr($comment->author_display_name, 0, 2));
        @endphp
        @if ($avatarUrl)
            <img src="{{ $avatarUrl }}" alt="" class="h-11 w-11 shrink-0 rounded-2xl object-cover ring-2 ring-stone-100 dark:ring-slate-700" loading="lazy" />
        @else
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-600 text-xs font-bold text-white" aria-hidden="true">
                {{ $initials }}
            </div>
        @endif
        <div class="min-w-0 flex-1">
            <p class="font-semibold text-stone-900 dark:text-stone-100">{{ $comment->author_display_name }}</p>
            <p class="text-xs text-stone-500 dark:text-stone-400">{{ $comment->created_at->diffForHumans() }}</p>
        </div>
        @if (! $isReply)
            <button type="button" wire:click="setReply({{ $comment->id }})" class="shrink-0 text-sm font-semibold text-violet-600 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300">
                {{ __('mksine::frontend.reply') }}
            </button>
        @endif
    </div>
    @if ($comment->hasRating())
        <div class="mb-2 flex gap-0.5" aria-label="{{ __('mksine::frontend.rating') }}: {{ $comment->rating }} {{ __('mksine::frontend.stars') }}">
            @for ($i = 1; $i <= 5; $i++)
                <span class="text-lg {{ $i <= $comment->rating ? 'text-amber-400' : 'text-stone-300 dark:text-stone-600' }}">★</span>
            @endfor
        </div>
    @endif
    <p class="text-sm leading-relaxed whitespace-pre-wrap text-stone-700 dark:text-stone-300">{{ $comment->content }}</p>

    @if ($comment->replies->isNotEmpty())
        <div class="mt-4 space-y-0">
            @foreach ($comment->replies as $reply)
                @include('mksine::themes.mksine.partials.comment-item', ['comment' => $reply, 'isReply' => true])
            @endforeach
        </div>
    @endif
</div>
