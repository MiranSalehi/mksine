@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex justify-center">
        <div class="inline-flex items-center gap-2">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-xl bg-stone-100 text-stone-400 dark:bg-slate-800 dark:text-stone-600" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-stone-200 bg-white text-stone-600 shadow-sm transition hover:border-violet-200 hover:bg-violet-50/80 hover:text-violet-700 focus:border-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-stone-300 dark:hover:border-violet-800 dark:hover:bg-violet-950/40 dark:hover:text-violet-300" aria-label="{{ __('pagination.previous') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
            @endif

            {{-- Page Numbers --}}
            <div class="inline-flex items-center gap-1">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex h-10 w-10 items-center justify-center text-stone-400 dark:text-stone-500">&#8230;</span>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="inline-flex h-10 min-w-[2.5rem] items-center justify-center rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 px-4 text-sm font-semibold text-white shadow-md shadow-violet-500/20 ring-2 ring-violet-500/25 dark:shadow-violet-900/40">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="inline-flex h-10 min-w-[2.5rem] items-center justify-center rounded-xl border border-stone-200 bg-white px-4 text-sm font-medium text-stone-600 shadow-sm transition hover:border-violet-200 hover:bg-violet-50/80 hover:text-violet-700 focus:border-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-stone-300 dark:hover:border-violet-800 dark:hover:bg-violet-950/40 dark:hover:text-violet-300" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-stone-200 bg-white text-stone-600 shadow-sm transition hover:border-violet-200 hover:bg-violet-50/80 hover:text-violet-700 focus:border-violet-400 focus:outline-none focus:ring-2 focus:ring-violet-500/20 dark:border-slate-600 dark:bg-slate-900 dark:text-stone-300 dark:hover:border-violet-800 dark:hover:bg-violet-950/40 dark:hover:text-violet-300" aria-label="{{ __('pagination.next') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @else
                <span class="inline-flex h-10 w-10 cursor-not-allowed items-center justify-center rounded-xl bg-stone-100 text-stone-400 dark:bg-slate-800 dark:text-stone-600" aria-hidden="true">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
