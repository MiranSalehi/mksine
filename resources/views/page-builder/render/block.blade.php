@php
    $type = $block['type'] ?? 'unknown';
    $data = $block['data'] ?? [];
    $children = $block['children'] ?? null;

    $registry = app(\Miran\Mksine\Core\PageBuilder\ComponentRegistry::class);
    $viewName = $registry->resolveRenderView($type);

    $includeData = ['data' => $data];
    if (array_key_exists('children', $block)) {
        $includeData['children'] = $children;
    }
@endphp
@if ($viewName !== '' && \Illuminate\Support\Facades\View::exists($viewName))
    @include($viewName, $includeData)
@else
    {{-- Unknown component or missing view --}}
    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg text-yellow-800 dark:text-yellow-200 text-sm">
        {{ __('mksine::page_builder.unknown_component_type') }} {{ $type }}
    </div>
@endif
