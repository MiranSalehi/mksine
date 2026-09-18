<div>
    @themeDoAction('entries.before_breadcrumb')
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="container mx-auto max-w-6xl px-4 py-3">
            <div class="text-sm text-gray-600 dark:text-gray-400 flex flex-wrap items-center gap-x-2 gap-y-1">
                <a href="{{ route('home') }}" class="text-blue-500 hover:text-blue-600">{{ __('mksine::frontend.home') }}</a>
                <span class="text-gray-400 dark:text-gray-500" aria-hidden="true">/</span>
                <span class="text-gray-800 dark:text-gray-200">{{ $type->pluralLabel }}</span>
            </div>
        </div>
    </div>
    @themeDoAction('entries.after_breadcrumb')

    @themeDoAction('entries.before_content')
    <div class="container mx-auto max-w-6xl px-4 py-12">
        <header class="mb-10">
            <h1 class="text-4xl font-bold text-gray-800 dark:text-gray-100">{{ $type->pluralLabel }}</h1>
        </header>

        @if($entries->isEmpty())
            <p class="text-gray-600 dark:text-gray-400">—</p>
        @else
            <ul class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($entries as $entry)
                    <li>
                        <a href="{{ $entry->url() }}" class="block group rounded-lg border border-gray-200 dark:border-gray-700 p-6 hover:border-blue-400 dark:hover:border-blue-500 transition">
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 group-hover:text-blue-600 dark:group-hover:text-blue-400">{{ $entry->title }}</h2>
                            @if($entry->excerpt)
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 line-clamp-3">{{ $entry->excerpt }}</p>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-10">
                {{ $entries->links() }}
            </div>
        @endif
    </div>
    @themeDoAction('entries.after_content')
</div>
