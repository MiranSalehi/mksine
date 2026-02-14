@props(['comment', 'isReply' => false])

<div class="{{ $isReply ? 'ml-6 sm:ml-10 mt-4 pl-4 border-l-2 border-gray-200 dark:border-gray-600' : '' }} border-b border-gray-200 dark:border-gray-700 pb-6 mb-6 last:border-b-0 last:pb-0 last:mb-0">
    <div class="flex gap-4 mb-3">
        @php
            $user = $comment->user;
            $avatarUrl = $user && method_exists($user, 'avatar_url') ? $user->avatar_url : null;
            $initials = $user && method_exists($user, 'initials') ? $user->initials() : strtoupper(mb_substr($comment->author_display_name, 0, 2));
        @endphp
        @if($avatarUrl)
            <img src="{{ $avatarUrl }}" alt="{{ $comment->author_display_name }}" class="w-10 h-10 rounded-full object-cover">
        @else
            <div class="w-10 h-10 rounded-full bg-pink-100 dark:bg-pink-900/40 flex items-center justify-center text-sm font-bold text-pink-600 dark:text-pink-400 shrink-0">
                {{ $initials }}
            </div>
        @endif
        <div class="min-w-0 flex-1">
            <p class="font-semibold text-gray-800 dark:text-gray-100">{{ $comment->author_display_name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $comment->created_at->diffForHumans() }}</p>
        </div>
        @if(!$isReply)
            <button type="button" wire:click="setReply({{ $comment->id }})"
                    class="text-sm text-pink-500 hover:text-pink-600 dark:text-pink-400">{{ __('Reply') }}</button>
        @endif
    </div>
    @if($comment->hasRating())
        <div class="flex gap-0.5 mb-2" aria-label="{{ $comment->rating }} {{ __('stars') }}">
            @for($i = 1; $i <= 5; $i++)
                <span class="text-lg {{ $i <= $comment->rating ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600' }}">★</span>
            @endfor
        </div>
    @endif
    <p class="text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap">{{ $comment->content }}</p>

    @if($comment->replies->isNotEmpty())
        <div class="mt-4 space-y-0">
            @foreach($comment->replies as $reply)
                @include('mksine::themes.mksine.partials.comment-item', ['comment' => $reply, 'isReply' => true])
            @endforeach
        </div>
    @endif
</div>
