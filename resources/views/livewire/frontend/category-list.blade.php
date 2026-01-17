<div>
    <h1 class="text-3xl font-bold mb-6">Categories</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @foreach($categories as $category)
            <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
                <h2 class="text-xl font-semibold mb-2">
                    <a href="{{ route('categories.show', $category->slug) }}" class="hover:underline">
                        {{ $category->name }}
                    </a>
                </h2>
                
                @if($category->children->count())
                    <ul class="mt-2 ml-4 list-disc text-gray-600">
                        @foreach($category->children as $child)
                            <li>
                                <a href="{{ route('categories.show', $child->slug) }}" class="hover:text-blue-600">
                                    {{ $child->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>
</div>
