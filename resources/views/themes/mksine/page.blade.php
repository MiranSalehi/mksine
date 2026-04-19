@php
    $isBuilder = $page->usesBuilder() && ! empty($page->builder_payload);
    $showPageHeader = (bool) ($page->show_page_header ?? true);
    $builderFullWidth = $isBuilder && (($page->builder_content_width ?? 'contained') === 'full');
    $mainClasses = $builderFullWidth
        ? 'w-full'
        : 'container mx-auto max-w-4xl px-4 py-12';
@endphp
<div>
    @if ($showPageHeader)
        @themeDoAction('page.before_breadcrumb')
        <!-- Breadcrumb -->
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="container mx-auto max-w-6xl px-4 py-3">
                <div class="text-sm text-gray-600 dark:text-gray-400 flex flex-wrap items-center gap-x-2 gap-y-1">
                    <a href="{{ route('home') }}" class="text-blue-500 hover:text-blue-600">{{ __('mksine::frontend.home') }}</a>
                    <span class="text-gray-400 dark:text-gray-500" aria-hidden="true">/</span>
                    <span class="text-gray-800 dark:text-gray-200">{{ $page->title }}</span>
                </div>
            </div>
        </div>
        @themeDoAction('page.after_breadcrumb')
    @endif

    @themeDoAction('page.before_content')
    <!-- Main Content -->
    <div class="{{ $mainClasses }}">
        <article>
            @if ($showPageHeader)
                <header class="mb-8">
                    <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-100">{{ $page->title }}</h1>
                    @if($page->published_at)
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ $page->published_at->format('M d, Y') }}</p>
                    @endif
                </header>
            @endif

            @if($isBuilder)
                {{-- Builder pages: optional full-width (landing) --}}
                <div class="builder-content space-y-0">
                    @foreach($page->builder_payload as $block)
                        @include('mksine::page-builder.render.block', ['block' => $block])
                    @endforeach
                </div>
            @else
                {{-- Regular pages: prose formatting --}}
                <div class="prose prose-lg max-w-none dark:prose-invert prose-headings:text-gray-800 dark:prose-headings:text-gray-100 prose-p:text-gray-600 dark:prose-p:text-gray-300">
                    {!! $page->content !!}
                </div>
            @endif
        </article>
    </div>
    @themeDoAction('page.after_content')
</div>
