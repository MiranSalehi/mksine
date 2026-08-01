@php
    $variant = $variant ?? 'full';
@endphp
<div
    class="mt-12 scroll-mt-24 rounded-2xl border border-stone-200/90 bg-white/95 p-5 dark:border-slate-700 dark:bg-slate-900/85 sm:p-8"
    id="comments-section"
    wire:ignore.self
    x-data
    @focus-comment-form="document.getElementById('comment-form')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
>
    @if (session('comment_message'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/40 dark:text-emerald-100" role="status">
            {{ session('comment_message') }}
        </div>
    @endif

    @if ($variant !== 'form_only')
        <h3 class="mb-6 text-lg font-bold text-stone-900 dark:text-stone-100">
            {{ __('mksine::frontend.comments') }}
            @if ($comments->isNotEmpty())
                <span class="font-normal text-stone-500 dark:text-stone-400">({{ $comments->count() }})</span>
            @endif
        </h3>

        @forelse ($comments as $comment)
            @include('mksine::themes.mksine.partials.comment-item', ['comment' => $comment, 'isReply' => false])
        @empty
            <p class="mb-6 text-sm text-stone-600 dark:text-stone-400">{{ __('mksine::frontend.no_comments_yet') }}</p>
        @endforelse
    @endif

    @if ($variant === 'full' || $variant === 'form_only')
        <div
            id="comment-form"
            @class([
                'scroll-mt-24 mt-8 border-t border-stone-200 pt-8 dark:border-slate-700' => $variant === 'full',
                'scroll-mt-24 mt-0 border-t-0 pt-0' => $variant === 'form_only',
            ])
        >
            <h4 class="mb-4 font-bold text-stone-900 dark:text-stone-100">{{ __('mksine::frontend.leave_a_comment') }}</h4>

            @if ($parentComment ?? null)
                <div class="mb-4 rounded-xl border border-violet-100 bg-violet-50/80 p-3 text-sm text-stone-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-stone-300">
                    {{ __('mksine::frontend.replying_to') }}: {{ $parentComment->author_display_name }}
                    <button type="button" wire:click="cancelReply" class="ms-2 font-semibold text-violet-600 hover:text-violet-700 dark:text-violet-400">{{ __('mksine::frontend.cancel') }}</button>
                </div>
            @endif

            <form wire:submit="submitComment" class="space-y-4">
                @if (! auth()->check())
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="comment_author_name" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('mksine::frontend.your_name') }} <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                id="comment_author_name"
                                wire:model.live="author_name"
                                class="w-full rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-stone-900 shadow-sm focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-stone-100 dark:focus:border-violet-500"
                                placeholder="{{ __('mksine::frontend.your_name') }}"
                            />
                            @error('author_name')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="comment_author_email" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('mksine::frontend.your_email') }} <span class="text-red-500">*</span></label>
                            <input
                                type="email"
                                id="comment_author_email"
                                wire:model.live="author_email"
                                class="w-full rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-stone-900 shadow-sm focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-stone-100 dark:focus:border-violet-500"
                                placeholder="{{ __('mksine::frontend.your_email') }}"
                            />
                            @error('author_email')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endif

                @if (! $this->parent_id)
                    <div>
                        <label class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('mksine::frontend.rating') }} ({{ __('mksine::frontend.optional') }})</label>
                        <div class="flex gap-1">
                            @for ($i = 1; $i <= 5; $i++)
                                <button
                                    type="button"
                                    wire:click="setRating({{ $i }})"
                                    class="p-1 text-2xl leading-none transition focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 rounded {{ ($rating ?? 0) >= $i ? 'text-amber-400' : 'text-stone-300 hover:text-amber-200 dark:text-stone-600' }}"
                                    aria-label="{{ $i }} {{ __('mksine::frontend.stars') }}"
                                >
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
                    <label for="comment_content" class="mb-1 block text-sm font-medium text-stone-700 dark:text-stone-300">{{ __('mksine::frontend.comment') }} <span class="text-red-500">*</span></label>
                    <textarea
                        id="comment_content"
                        wire:model.live="content"
                        rows="4"
                        class="w-full rounded-xl border border-stone-200 bg-white px-4 py-2.5 text-stone-900 shadow-sm focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-800 dark:text-stone-100 dark:focus:border-violet-500"
                        placeholder="{{ __('mksine::frontend.write_comment') }}"
                    ></textarea>
                    @error('content')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-violet-500 hover:to-indigo-500 focus-visible:outline focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2 disabled:opacity-50 dark:focus-visible:ring-offset-slate-900"
                >
                    <span wire:loading.remove wire:target="submitComment">{{ __('mksine::frontend.submit_comment') }}</span>
                    <span wire:loading wire:target="submitComment">{{ __('mksine::frontend.sending') }}</span>
                </button>
            </form>
        </div>
    @endif
</div>
