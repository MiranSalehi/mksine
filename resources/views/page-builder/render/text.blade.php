@php
    $content = $data['content'] ?? '';
@endphp

<div class="prose prose-lg dark:prose-invert max-w-none mb-6">
    {!! $content !!}
</div>
