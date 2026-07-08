@php
    $content = $data['content'] ?? '';
@endphp

<div class="builder-rich-text mb-8 md:mb-10 max-w-none prose prose-lg dark:prose-invert [&_a]:font-medium [&_a]:text-amber-600 [&_a]:no-underline hover:[&_a]:underline dark:[&_a]:text-amber-400">
    {!! mks_render_content($content) !!}
</div>
