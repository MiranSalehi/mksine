<div>
    <!-- Breadcrumb -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="container mx-auto max-w-6xl px-4 py-3">
            <div class="text-sm text-gray-600 dark:text-gray-400 flex flex-wrap items-center gap-x-2 gap-y-1">
                <a href="{{ route('home') }}" class="text-pink-500 hover:text-pink-600">{{ __('Home') }}</a>
                <span class="text-gray-400 dark:text-gray-500" aria-hidden="true">/</span>
                <span class="text-gray-800 dark:text-gray-200">{{ $page->title }}</span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto max-w-4xl px-4 py-12">
        <article>
            <header class="mb-8">
                <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-100">{{ $page->title }}</h1>
                @if($page->published_at)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ $page->published_at->format('M d, Y') }}</p>
                @endif
            </header>

            @if($page->usesBuilder() && !empty($page->builder_payload))
                {{-- Builder pages: full-width, no prose constraint --}}
                <div class="builder-content space-y-8">
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
</div>
