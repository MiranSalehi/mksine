@if ($item->hasChildren())
    <li @class([
        'mksine-admin-bar__menu-item',
        'mksine-admin-bar__menu-item--has-children',
    ])>
        <button
            type="button"
            class="mksine-admin-bar__link mksine-admin-bar__dropdown-toggle"
            aria-haspopup="true"
            aria-expanded="false"
        >{{ $item->label }}</button>

        <ul class="mksine-admin-bar__dropdown" role="menu">
            @foreach ($item->children as $child)
                @include('mksine::partials.frontend-admin-bar-menu-item', ['item' => $child, 'nested' => true])
            @endforeach
        </ul>
    </li>
@elseif ($item->isLink())
    @if ($nested ?? false)
        <li class="mksine-admin-bar__dropdown-item" role="none">
            <a
                href="{{ $item->url }}"
                class="mksine-admin-bar__dropdown-link"
                role="menuitem"
                @if ($item->openInNewTab) target="_blank" rel="noopener noreferrer" @endif
            >{{ $item->label }}</a>
        </li>
    @else
        <li class="mksine-admin-bar__menu-item">
            <a
                href="{{ $item->url }}"
                class="mksine-admin-bar__link"
                @if ($item->openInNewTab) target="_blank" rel="noopener noreferrer" @endif
            >{{ $item->label }}</a>
        </li>
    @endif
@endif
