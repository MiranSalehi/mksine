<div>
    @themeDoAction('categories.before_breadcrumb')
    <!-- Breadcrumb -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="container mx-auto max-w-6xl px-4 py-3">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <a href="{{ route('home') }}" class="text-blue-500 hover:text-blue-600">{{ __('mksine::frontend.home') }}</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800 dark:text-gray-200">{{ __('mksine::frontend.categories') }}</span>
            </div>
        </div>
    </div>
    @themeDoAction('categories.after_breadcrumb')

    @themeDoAction('categories.before_header')
    <!-- Page Header -->
    <section class="bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-12">
        <div class="container mx-auto max-w-6xl px-4">
            <h1 class="text-4xl md:text-5xl font-bold mb-2">{{ __('mksine::frontend.all_categories') }}</h1>
            <p class="text-blue-100">{{ __('mksine::frontend.browse_by_category') }}</p>
        </div>
    </section>
    @themeDoAction('categories.after_header')

    @themeDoAction('categories.before_content')
    <!-- Main Content -->
    <div class="container mx-auto max-w-6xl px-4 py-12">
        <div class="space-y-12">
            @forelse($categories as $category)
                <section>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6 flex items-center gap-2">
                        <a href="{{ $category->getUrl() }}" class="hover:text-blue-500 transition">
                            {{ $category->name }}
                        </a>
                        @if($category->posts_count > 0)
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $category->posts_count }} {{ __('mksine::frontend.articles') }})</span>
                        @endif
                    </h2>

                    @if($category->description)
                        <p class="text-gray-600 dark:text-gray-400 mb-4 max-w-2xl">{{ $category->description }}</p>
                    @endif

                    @if($category->children->isNotEmpty())
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            @foreach($category->children as $child)
                                <a href="{{ $child->getUrl() }}" class="block bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-blue-500 dark:hover:border-blue-500 p-6 text-center transition shadow-sm hover:shadow-md">
                                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-1">{{ $child->name }}</h3>
                                    @if(isset($child->posts_count) && $child->posts_count > 0)
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $child->posts_count }} {{ __('mksine::frontend.articles') }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @else
                        <a href="{{ $category->getUrl() }}" class="inline-block bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 hover:border-blue-500 rounded-lg px-6 py-4 font-semibold text-gray-800 dark:text-gray-200 transition">
                            {{ __('mksine::frontend.view_articles') }} →
                        </a>
                    @endif
                </section>
            @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-12">{{ __('mksine::frontend.no_categories_yet') }}</p>
            @endforelse
        </div>
    </div>
    @themeDoAction('categories.after_content')
</div>
