<div>
    @themeDoAction('entry.before_breadcrumb')
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="container mx-auto max-w-6xl px-4 py-3">
            <div class="text-sm text-gray-600 dark:text-gray-400 flex flex-wrap items-center gap-x-2 gap-y-1">
                <a href="{{ route('home') }}" class="text-blue-500 hover:text-blue-600">{{ __('mksine::frontend.home') }}</a>
                <span class="text-gray-400 dark:text-gray-500" aria-hidden="true">/</span>
                @if($type->hasArchive)
                    <a href="{{ $type->archivePath() }}" class="text-blue-500 hover:text-blue-600">{{ $type->pluralLabel }}</a>
                    <span class="text-gray-400 dark:text-gray-500" aria-hidden="true">/</span>
                @endif
                <span class="text-gray-800 dark:text-gray-200">{{ $entry->title }}</span>
            </div>
        </div>
    </div>
    @themeDoAction('entry.after_breadcrumb')

    @themeDoAction('entry.before_content')
    <div class="container mx-auto max-w-4xl px-4 py-12">
        <article>
            <header class="mb-8">
                <p class="text-sm font-medium text-blue-600 dark:text-blue-400 mb-2">{{ $type->singularLabel }}</p>
                <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-100">{{ $entry->title }}</h1>
                @if($entry->published_at)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ $entry->published_at->format('M d, Y') }}</p>
                @endif
            </header>

            @if($entry->featuredImage)
                <div class="mb-8 overflow-hidden rounded-lg">
                    <img src="{{ $entry->featuredImage->full_url ?? $entry->featured_image }}" alt="" class="w-full h-auto">
                </div>
            @endif

            <div class="prose prose-lg max-w-none dark:prose-invert prose-headings:text-gray-800 dark:prose-headings:text-gray-100 prose-p:text-gray-600 dark:prose-p:text-gray-300">
                {!! mks_render_content($entry->content) !!}
            </div>
        </article>
    </div>
    @themeDoAction('entry.after_content')
</div>
