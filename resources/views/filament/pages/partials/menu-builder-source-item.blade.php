@props(['item', 'depth' => 0, 'sourceKey' => ''])

<label
    class="flex cursor-pointer items-center gap-2.5 rounded-md py-1.5 pe-2 transition-colors hover:bg-zinc-50 dark:hover:bg-white/[0.05]"
    style="padding-inline-start: {{ $depth * 16 + 8 }}px;"
>
    <input
        type="checkbox"
        wire:model="sourceFormData.{{ $sourceKey }}.selected"
        value="{{ $item['id'] }}"
        class="size-4 shrink-0 rounded border-zinc-300 text-violet-600 focus:ring-violet-500 dark:border-zinc-600"
    />
    <span class="truncate text-xs text-zinc-700 dark:text-zinc-300">{{ $item['label'] }}</span>
</label>

@if(!empty($item['children']))
    @foreach($item['children'] as $child)
        @include('mksine::filament.pages.partials.menu-builder-source-item', [
            'item'      => $child,
            'depth'     => $depth + 1,
            'sourceKey' => $sourceKey,
        ])
    @endforeach
@endif
