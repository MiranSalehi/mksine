@php
    if (! isset($scrollTo)) {
        $scrollTo = false;
    }

    $scrollIntoViewJsSnippet = ($scrollTo !== false)
        ? <<<JS
           (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}'))?.scrollIntoView({ block: 'nearest' })
        JS
        : '';

    $pageName = $paginator->getPageName();
@endphp

@if ($paginator->hasPages())
    <nav
        role="navigation"
        aria-label="{{ __('mksine::media_picker.pagination_label') }}"
        class="mksine-media-picker-pagination flex flex-col items-center gap-3 sm:flex-row sm:justify-between"
    >
        <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ __('mksine::media_picker.pagination_summary', [
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ]) }}
        </p>

        <div class="inline-flex items-center gap-1.5 rtl:flex-row-reverse">
            @if ($paginator->onFirstPage())
                <span
                    class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-600"
                    aria-hidden="true"
                >
                    <x-heroicon-o-chevron-left class="h-4 w-4 rtl:rotate-180" />
                </span>
            @else
                <button
                    type="button"
                    wire:click="previousPage('{{ $pageName }}')"
                    @if ($scrollIntoViewJsSnippet !== '') x-on:click="{{ $scrollIntoViewJsSnippet }}" @endif
                    wire:loading.attr="disabled"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-primary-500/50 dark:hover:bg-primary-500/10 dark:hover:text-primary-400"
                    aria-label="{{ __('pagination.previous') }}"
                >
                    <x-heroicon-o-chevron-left class="h-4 w-4 rtl:rotate-180" />
                </button>
            @endif

            <div class="inline-flex items-center gap-1 rtl:flex-row-reverse">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex h-9 min-w-9 items-center justify-center px-1 text-sm text-gray-400 dark:text-gray-500" aria-hidden="true">&hellip;</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <span wire:key="media-picker-{{ $pageName }}-page-{{ $page }}">
                                @if ($page == $paginator->currentPage())
                                    <span
                                        class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg bg-primary-600 px-3 text-sm font-semibold text-white shadow-sm dark:bg-primary-500"
                                        aria-current="page"
                                    >
                                        {{ $page }}
                                    </span>
                                @else
                                    <button
                                        type="button"
                                        wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                                        @if ($scrollIntoViewJsSnippet !== '') x-on:click="{{ $scrollIntoViewJsSnippet }}" @endif
                                        wire:loading.attr="disabled"
                                        class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-primary-500/50 dark:hover:bg-primary-500/10 dark:hover:text-primary-400"
                                        aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                    >
                                        {{ $page }}
                                    </button>
                                @endif
                            </span>
                        @endforeach
                    @endif
                @endforeach
            </div>

            @if ($paginator->hasMorePages())
                <button
                    type="button"
                    wire:click="nextPage('{{ $pageName }}')"
                    @if ($scrollIntoViewJsSnippet !== '') x-on:click="{{ $scrollIntoViewJsSnippet }}" @endif
                    wire:loading.attr="disabled"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500/20 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-primary-500/50 dark:hover:bg-primary-500/10 dark:hover:text-primary-400"
                    aria-label="{{ __('pagination.next') }}"
                >
                    <x-heroicon-o-chevron-right class="h-4 w-4 rtl:rotate-180" />
                </button>
            @else
                <span
                    class="inline-flex h-9 w-9 cursor-not-allowed items-center justify-center rounded-lg bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-600"
                    aria-hidden="true"
                >
                    <x-heroicon-o-chevron-right class="h-4 w-4 rtl:rotate-180" />
                </span>
            @endif
        </div>
    </nav>
@endif
