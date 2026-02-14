@php
    $type = $block['type'] ?? 'unknown';
    $data = $block['data'] ?? [];
    $children = $block['children'] ?? null;
@endphp

@switch($type)
    @case('heading')
        @include('mksine::page-builder.render.heading', ['data' => $data])
        @break

    @case('text')
        @include('mksine::page-builder.render.text', ['data' => $data])
        @break

    @case('image')
        @include('mksine::page-builder.render.image', ['data' => $data])
        @break

    @case('button')
        @include('mksine::page-builder.render.button', ['data' => $data])
        @break

    @case('spacer')
        @include('mksine::page-builder.render.spacer', ['data' => $data])
        @break

    @case('divider')
        @include('mksine::page-builder.render.divider', ['data' => $data])
        @break

    @case('columns')
        @include('mksine::page-builder.render.columns', ['data' => $data, 'children' => $children])
        @break

    @case('hero')
        @include('mksine::page-builder.render.hero', ['data' => $data])
        @break

    @case('cta')
        @include('mksine::page-builder.render.cta', ['data' => $data])
        @break

    @case('features')
        @include('mksine::page-builder.render.features', ['data' => $data])
        @break

    @case('testimonial')
        @include('mksine::page-builder.render.testimonial', ['data' => $data])
        @break

    @case('accordion')
        @include('mksine::page-builder.render.accordion', ['data' => $data])
        @break

    @case('tabs')
        @include('mksine::page-builder.render.tabs', ['data' => $data])
        @break

    @case('slider')
        @include('mksine::page-builder.render.slider', ['data' => $data])
        @break

    @default
        {{-- Unknown component --}}
        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg text-yellow-800 dark:text-yellow-200 text-sm">
            {{ __('Unknown component type:') }} {{ $type }}
        </div>
@endswitch