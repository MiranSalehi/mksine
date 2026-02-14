@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex justify-center">
        <div class="inline-flex items-center gap-2">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-white text-gray-600 shadow-sm border border-gray-200 hover:bg-pink-50 hover:border-pink-200 hover:text-pink-600 focus:outline-none focus:ring-2 focus:ring-pink-500/20 focus:border-pink-400 transition-all duration-200" aria-label="{{ __('pagination.previous') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
            @endif

            {{-- Page Numbers --}}
            <div class="inline-flex items-center gap-1">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex items-center justify-center w-10 h-10 text-gray-400">&#8230;</span>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="inline-flex items-center justify-center min-w-[2.5rem] h-10 px-4 rounded-lg bg-gradient-to-r from-pink-500 to-red-500 text-white font-semibold text-sm shadow-md shadow-pink-500/25 ring-2 ring-pink-500/20">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="inline-flex items-center justify-center min-w-[2.5rem] h-10 px-4 rounded-lg bg-white text-gray-600 font-medium text-sm shadow-sm border border-gray-200 hover:bg-pink-50 hover:border-pink-200 hover:text-pink-600 focus:outline-none focus:ring-2 focus:ring-pink-500/20 focus:border-pink-400 transition-all duration-200" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-white text-gray-600 shadow-sm border border-gray-200 hover:bg-pink-50 hover:border-pink-200 hover:text-pink-600 focus:outline-none focus:ring-2 focus:ring-pink-500/20 focus:border-pink-400 transition-all duration-200" aria-label="{{ __('pagination.next') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @else
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed" aria-hidden="true">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
