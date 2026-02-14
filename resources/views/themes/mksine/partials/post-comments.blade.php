<div class="bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 p-6 rounded-lg" id="comments-section" wire:ignore.self>
    <h3 class="font-bold text-gray-800 dark:text-gray-100 mb-6">
        {{ __('Comments') }}
        @if($comments->isNotEmpty())
            <span class="text-gray-500 dark:text-gray-400 font-normal">({{ $comments->count() }})</span>
        @endif
    </h3>

    @if(session('comment_message'))
        <div class="mb-6 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 border border-green-200 dark:border-green-800">
            {{ session('comment_message') }}
        </div>
    @endif

    {{-- Comment list --}}
    @forelse($comments as $comment)
        @include('mksine::themes.mksine.partials.comment-item', ['comment' => $comment, 'isReply' => false])
    @empty
        <p class="text-gray-600 dark:text-gray-400 text-sm mb-6">{{ __('No comments yet. Be the first to comment!') }}</p>
    @endforelse

    {{-- Comment form --}}
    <div id="comment-form" class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
        <h4 class="font-bold text-gray-800 dark:text-gray-100 mb-4">{{ __('Leave a Comment') }}</h4>

        @if($parentComment ?? null)
            <div class="mb-4 p-3 rounded bg-gray-100 dark:bg-gray-700/50 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Replying to') }}: {{ $parentComment->author_display_name }}
                <button type="button" wire:click="cancelReply" class="ml-2 text-pink-500 hover:text-pink-600">{{ __('Cancel') }}</button>
            </div>
        @endif

        <form wire:submit="submitComment" class="space-y-4">
            @if(!auth()->check())
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="comment_author_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Name') }} <span class="text-red-500">*</span></label>
                        <input type="text" id="comment_author_name" wire:model="author_name"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500 dark:bg-gray-700 dark:text-gray-100"
                               placeholder="{{ __('Your name') }}">
                        @error('author_name')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="comment_author_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Email') }} <span class="text-red-500">*</span></label>
                        <input type="email" id="comment_author_email" wire:model="author_email"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500 dark:bg-gray-700 dark:text-gray-100"
                               placeholder="{{ __('Your email') }}">
                        @error('author_email')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @endif

            @if(!$this->parent_id)
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Rating') }} ({{ __('optional') }})</label>
                <div class="flex gap-1">
                    @for($i = 1; $i <= 5; $i++)
                        <button type="button" wire:click="$set('rating', {{ $i }})"
                                class="p-1 text-2xl leading-none focus:outline-none transition {{ ($rating ?? 0) >= $i ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600 hover:text-amber-200' }}"
                                aria-label="{{ $i }} {{ __('stars') }}">
                            ★
                        </button>
                    @endfor
                </div>
                @error('rating')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
            @endif

            <div>
                <label for="comment_content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Comment') }} <span class="text-red-500">*</span></label>
                <textarea id="comment_content" wire:model="content" rows="4"
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500 dark:bg-gray-700 dark:text-gray-100"
                          placeholder="{{ __('Write your comment...') }}"></textarea>
                @error('content')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled"
                    class="bg-pink-500 text-white px-6 py-2 rounded-lg hover:bg-pink-600 transition disabled:opacity-50">
                <span wire:loading.remove wire:target="submitComment">{{ __('Submit Comment') }}</span>
                <span wire:loading wire:target="submitComment">{{ __('Sending...') }}</span>
            </button>
        </form>
    </div>
</div>
