<div>
    <!-- Breadcrumb -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="container mx-auto max-w-6xl px-4 py-3">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <a href="{{ route('home') }}" class="text-pink-500 hover:text-pink-600">{{ __('Home') }}</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800 dark:text-gray-200">{{ __('Categories') }}</span>
            </div>
        </div>
    </div>

    <!-- Page Header -->
    <section class="bg-gradient-to-r from-pink-500 to-red-400 text-white py-12">
        <div class="container mx-auto max-w-6xl px-4">
            <h1 class="text-4xl md:text-5xl font-bold mb-2">{{ __('All Categories') }}</h1>
            <p class="text-pink-100">{{ __('Browse all content by category') }}</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container mx-auto max-w-6xl px-4 py-12">
        <div class="space-y-12">
            @forelse($categories as $category)
                <section>
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6 flex items-center gap-2">
                        <a href="{{ $category->getUrl() }}" class="hover:text-pink-500 transition">
                            {{ $category->name }}
                        </a>
                        @if($category->posts_count > 0)
                            <span class="text-sm font-normal text-gray-500 dark:text-gray-400">({{ $category->posts_count }} {{ __('Articles') }})</span>
                        @endif
                    </h2>

                    @if($category->description)
                        <p class="text-gray-600 dark:text-gray-400 mb-4 max-w-2xl">{{ $category->description }}</p>
                    @endif

                    @if($category->children->isNotEmpty())
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            @foreach($category->children as $child)
                                <a href="{{ $child->getUrl() }}" class="block bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-pink-500 dark:hover:border-pink-500 p-6 text-center transition shadow-sm hover:shadow-md">
                                    <h3 class="font-semibold text-gray-800 dark:text-gray-200 mb-1">{{ $child->name }}</h3>
                                    @if(isset($child->posts_count) && $child->posts_count > 0)
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $child->posts_count }} {{ __('Articles') }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @else
                        <a href="{{ $category->getUrl() }}" class="inline-block bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 hover:border-pink-500 rounded-lg px-6 py-4 font-semibold text-gray-800 dark:text-gray-200 transition">
                            {{ __('View articles') }} →
                        </a>
                    @endif
                </section>
            @empty
                <p class="text-gray-500 dark:text-gray-400 text-center py-12">{{ __('No categories yet.') }}</p>
            @endforelse
        </div>
    </div>
</div>
