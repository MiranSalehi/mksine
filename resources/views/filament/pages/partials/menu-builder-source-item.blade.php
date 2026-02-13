@props(['item', 'depth' => 0, 'sourceKey' => ''])

<label class="flex items-center gap-2 cursor-pointer py-0.5" style="padding-left: {{ $depth * 16 }}px;">
    <input
        type="checkbox"
        wire:model="sourceFormData.{{ $sourceKey }}.selected"
        value="{{ $item['id'] }}"
        class="shrink-0 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
    />
    <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ $item['label'] }}</span>
</label>
@if(!empty($item['children']))
    @foreach($item['children'] as $child)
        @include('mksine::filament.pages.partials.menu-builder-source-item', [
            'item' => $child,
            'depth' => $depth + 1,
            'sourceKey' => $sourceKey,
        ])
    @endforeach
@endif
