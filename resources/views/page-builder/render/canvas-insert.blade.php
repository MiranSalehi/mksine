<div
    class="mksine-insert{{ ! empty($visible) ? ' is-visible' : '' }}"
    data-mksine-insert="{{ (int) $position }}"
    @if (is_string($parentId ?? null) && $parentId !== '') data-mksine-parent="{{ $parentId }}" @endif
    @if (is_numeric($columnIndex ?? null)) data-mksine-column="{{ (int) $columnIndex }}" @endif
>
    <button type="button" data-mksine-action="insert" title="{{ __('mksine::page_builder.add_component_here') }}" aria-label="{{ __('mksine::page_builder.add_component_here') }}">
        <span class="mksine-insert-plus" aria-hidden="true">+</span>
        <span class="mksine-insert-label">{{ __('mksine::page_builder.add_component_here') }}</span>
    </button>
</div>
