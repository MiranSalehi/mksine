@if ($items !== [])
    <div id="mksine-admin-bar" class="mksine-admin-bar" role="navigation" aria-label="{{ __('mksine::frontend_admin_bar.aria_label') }}">
        <div class="mksine-admin-bar__inner">
            <ul class="mksine-admin-bar__menu">
                @foreach ($items as $item)
                    @include('mksine::partials.frontend-admin-bar-menu-item', ['item' => $item, 'nested' => false])
                @endforeach
            </ul>

            @if ($userName !== '')
                <span class="mksine-admin-bar__user">{{ $userName }}</span>
            @endif
        </div>
    </div>

    <style>
        :root { --mksine-admin-bar-height: 32px; }
        html.mksine-has-admin-bar { scroll-padding-top: var(--mksine-admin-bar-height); }
        html.mksine-has-admin-bar body { padding-top: var(--mksine-admin-bar-height); }
        html.mksine-has-admin-bar .site-header-bar { top: var(--mksine-admin-bar-height); }
        html.mksine-has-admin-bar header.sticky { top: var(--mksine-admin-bar-height); }

        .mksine-admin-bar {
            position: fixed;
            inset: 0 0 auto 0;
            z-index: 99999;
            height: var(--mksine-admin-bar-height);
            background: #1d2327;
            color: #f0f0f1;
            font: 13px/32px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            box-shadow: 0 1px 0 rgba(0, 0, 0, 0.25);
        }

        .mksine-admin-bar__inner {
            display: flex;
            align-items: center;
            gap: 12px;
            height: 100%;
            max-width: 100%;
            padding: 0 12px;
        }

        .mksine-admin-bar__link,
        .mksine-admin-bar__dropdown-link {
            color: #f0f0f1;
            text-decoration: none;
            white-space: nowrap;
        }

        .mksine-admin-bar__menu {
            display: flex;
            align-items: center;
            gap: 2px;
            margin: 0;
            padding: 0;
            list-style: none;
            overflow-x: auto;
            flex: 1 1 auto;
            min-width: 0;
        }

        .mksine-admin-bar__menu-item { margin: 0; position: relative; }

        .mksine-admin-bar__link,
        .mksine-admin-bar__dropdown-toggle {
            display: inline-block;
            padding: 0 8px;
            border-radius: 2px;
            border: 0;
            background: transparent;
            font: inherit;
            cursor: pointer;
        }

        .mksine-admin-bar__link:hover,
        .mksine-admin-bar__dropdown-toggle:hover,
        .mksine-admin-bar__dropdown-link:hover {
            color: #72aee6;
        }

        .mksine-admin-bar__link:hover,
        .mksine-admin-bar__dropdown-toggle:hover {
            background: rgba(240, 240, 241, 0.08);
        }

        .mksine-admin-bar__menu-item--has-children:hover > .mksine-admin-bar__dropdown,
        .mksine-admin-bar__menu-item--has-children:focus-within > .mksine-admin-bar__dropdown {
            display: block;
        }

        .mksine-admin-bar__menu-item--has-children:hover > .mksine-admin-bar__dropdown-toggle,
        .mksine-admin-bar__menu-item--has-children:focus-within > .mksine-admin-bar__dropdown-toggle {
            color: #72aee6;
            background: rgba(240, 240, 241, 0.08);
        }

        .mksine-admin-bar__dropdown {
            display: none;
            position: absolute;
            inset-inline-start: 0;
            top: 100%;
            min-width: 180px;
            margin: 0;
            padding: 4px 0;
            list-style: none;
            background: #2c3338;
            border: 1px solid #3c434a;
            box-shadow: 0 3px 5px rgba(0, 0, 0, 0.2);
            z-index: 100000;
        }

        .mksine-admin-bar__dropdown-item { margin: 0; }

        .mksine-admin-bar__dropdown-link {
            display: block;
            padding: 6px 12px;
            line-height: 1.4;
        }

        .mksine-admin-bar__dropdown-link:hover {
            background: rgba(240, 240, 241, 0.08);
        }

        .mksine-admin-bar__user {
            margin-inline-start: auto;
            color: #c3c4c7;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
        }

        @media (max-width: 640px) {
            .mksine-admin-bar__user { display: none; }
        }
    </style>

    <script>
        document.documentElement.classList.add('mksine-has-admin-bar');
    </script>

    @themeDoAction('frontend_admin_bar.after')
@endif
