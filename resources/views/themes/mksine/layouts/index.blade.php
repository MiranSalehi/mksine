<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $siteFaviconUrl = mks_setting_media_url('favicon');
        $siteLogoUrl = mks_setting_media_url('logo');
        $siteName = mks_setting('site_name') ?: config('app.name', 'MKSine');
        $nameParts = preg_split('/\s+/', trim((string) $siteName), 2, PREG_SPLIT_NO_EMPTY);
        if (count($nameParts) >= 2) {
            $brandLeft = $nameParts[0];
            $brandRight = $nameParts[1];
        } elseif (strlen((string) $siteName) > 3 && str_ends_with(strtolower((string) $siteName), 'ine')) {
            $brandLeft = substr((string) $siteName, 0, -3);
            $brandRight = substr((string) $siteName, -3);
        } else {
            $brandLeft = (string) $siteName;
            $brandRight = '';
        }
        $localeCode = match (app()->getLocale()) {
            'fa' => 'fa',
            'en' => 'en',
            default => strtolower(substr(str_replace('_', '-', app()->getLocale()), 0, 2)),
        };
        $localePairShort = array_values(array_unique(array_map(
            static fn (string $l): string => strtoupper(substr(strtok($l, '_@'), 0, 2)),
            app(\Miran\Mksine\Core\Translation\TranslationFileManager::class)->getAvailableLocales()
        )));
        $localePairLabel = $localePairShort === []
            ? 'EN/FA'
            : implode('/', $localePairShort);
    @endphp
    <title>{{ $title ?? $siteName }}</title>
    @php
        $__metaDesc = isset($metaDescription) ? trim((string) $metaDescription) : '';
    @endphp
    @if ($__metaDesc !== '')
        <meta name="description" content="{{ $__metaDesc }}">
    @endif

    @themeAssets
    @if ($siteFaviconUrl)
        <link rel="icon" href="{{ $siteFaviconUrl }}" />
    @endif
</head>
<body>
    <!-- Main header: logo + mega nav (desktop) / CMS menu (mobile drawer) + locale + theme -->
    <header class="site-header-bar relative sticky top-0 z-50 border-b border-gray-200 bg-white shadow-[0_1px_0_rgba(0,0,0,0.08)] dark:border-gray-800 dark:shadow-[0_1px_0_rgba(255,255,255,0.06)]">
        <div class="mx-auto flex min-h-16 max-w-[1400px] items-center justify-between gap-4 px-6 py-3 lg:min-h-[4.75rem] lg:gap-8 lg:px-10 xl:px-14">
            <div class="flex min-w-0 flex-1 items-center gap-6 lg:gap-10 xl:gap-14">
                <a href="{{ url('/') }}" class="site-header-brand flex shrink-0 items-center gap-2.5 no-underline">
                    @if ($siteLogoUrl)
                        <img
                            src="{{ $siteLogoUrl }}"
                            alt="{{ $siteName }}"
                            class="site-header-logo-img h-9 w-auto max-w-[220px] shrink-0 object-contain dark:opacity-95"
                            loading="eager"
                            decoding="async"
                        />
                    @else
                        <span class="site-header-logo-mark flex h-9 w-9 shrink-0 items-center justify-center text-white shadow-none">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 17L17 7M17 7H9M17 7V15" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span class="text-lg font-bold tracking-tight text-gray-700 dark:text-gray-200">
                            <span class="text-gray-600 dark:text-gray-300">{{ $brandLeft }}</span>@if($brandRight !== '')<span class="site-header-brand-accent">{{ $brandRight }}</span>@endif
                        </span>
                    @endif
                </a>

                @include('mksine::themes.mksine.partials.site-header-mega-nav')
            </div>

            <div class="site-header-utils flex shrink-0 items-center gap-1 sm:gap-3">
                <button
                    type="button"
                    class="site-header-locale-toggle hidden items-center gap-2 text-sm sm:inline-flex"
                    data-direction-toggle
                    title="{{ __('Toggle reading direction') }}"
                    aria-label="{{ __('Toggle reading direction') }}"
                >
                    <span class="whitespace-nowrap font-medium text-gray-600 dark:text-gray-300">{{ $localePairLabel }}</span>
                    <span class="site-header-locale-toggle__dir-icon shrink-0 text-gray-500 dark:text-gray-400" aria-hidden="true">
                        <svg class="locale-dir-icon locale-dir-icon--ltr h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 10H21M17 6H3M17 14H3M3 18H21"/>
                        </svg>
                        <svg class="locale-dir-icon locale-dir-icon--rtl h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10H3M21 6H7M21 14H7M21 18H3"/>
                        </svg>
                    </span>
                </button>

                <button type="button" class="site-header-icon-btn theme-toggle hidden md:inline-flex" data-theme-toggle title="{{ __('Toggle theme') }}">
                    <svg class="sun-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1m-16 0H1m15.364 1.636l.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <svg class="moon-icon hidden h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                </button>

                {{-- Hamburger: wrapper ensures hidden on lg+ (avoids display conflicts with .site-header-icon-btn) --}}
                <div class="flex items-center lg:hidden">
                    <button
                        type="button"
                        class="site-mobile-menu-trigger"
                        data-mobile-menu
                        aria-expanded="false"
                        aria-controls="site-mobile-drawer"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        @include('mksine::themes.mksine.partials.site-header-mega-panels')
    </header>

    {{-- Mobile: overlay + slide-in drawer (sidebar) --}}
    <div
        id="site-mobile-backdrop"
        class="site-mobile-backdrop"
        data-mobile-backdrop
        aria-hidden="true"
    ></div>
    <div
        id="site-mobile-drawer"
        class="site-mobile-drawer"
        data-mobile-drawer
        role="dialog"
        aria-modal="true"
        aria-label="{{ __('Menu') }}"
        aria-hidden="true"
    >
        <div class="site-mobile-drawer__header">
            <span class="site-mobile-drawer__title">{{ __('Menu') }}</span>
            <button
                type="button"
                class="site-mobile-drawer__close"
                data-mobile-menu-close
                aria-label="{{ __('Close') }}"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <nav class="site-mobile-drawer__nav" aria-label="{{ __('Primary') }}">
            <x-mksine::menu location="header_primary" class="mksine-menu-header_primary--drawer" />
        </nav>
    </div>

    {!! $slot !!}

    <footer
        class="site-footer mt-auto border-t border-gray-200 bg-white pb-10 pt-4 text-[15px] text-gray-600 md:pb-12 md:pt-6 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-400 [&_a]:text-inherit [&_a:hover]:text-gray-900 dark:[&_a:hover]:text-gray-100"
        role="contentinfo"
    >
        <div class="mx-auto w-full max-w-[1400px] px-6 lg:px-10 xl:px-14">
            {{-- Footer columns; menu <ul> uses display:contents — top spacing only on <footer> to avoid double padding --}}
            <div class="border-b border-gray-200 pb-6 pt-0 dark:border-gray-800">
                <div class="footer-main grid min-w-0 grid-cols-2 items-start gap-x-4 gap-y-8 md:grid-cols-4 md:gap-x-6 md:gap-y-12">
                    {{-- Menu location key must match admin: "Footer links" → footer_links --}}
                    <x-mksine::menu location="footer_links" class="mksine-menu-footer_columns" />
                </div>
            </div>

            <nav class="flex flex-wrap items-center justify-center gap-4 pt-8" aria-label="{{ __('frontend.social_links') }}">
                <a href="#" class="opacity-60 transition hover:opacity-100" aria-label="{{ __('frontend.footer_social_facebook') }}" target="_blank" rel="nofollow noopener noreferrer">
                    <img src="{{ theme_asset('images/footer-social-facebook.svg') }}" alt="" width="18" height="18" loading="lazy"/>
                </a>
                <a href="#" class="opacity-60 transition hover:opacity-100" aria-label="{{ __('frontend.footer_social_x') }}" target="_blank" rel="nofollow noopener noreferrer">
                    <img src="{{ theme_asset('images/footer-social-x.svg') }}" alt="" width="18" height="18" loading="lazy"/>
                </a>
                <a href="#" class="opacity-60 transition hover:opacity-100" aria-label="{{ __('frontend.footer_social_linkedin') }}" target="_blank" rel="nofollow noopener noreferrer">
                    <img src="{{ theme_asset('images/footer-social-linkedin.svg') }}" alt="" width="18" height="18" loading="lazy"/>
                </a>
                <a href="#" class="opacity-60 transition hover:opacity-100" aria-label="{{ __('frontend.footer_social_instagram') }}" target="_blank" rel="nofollow noopener noreferrer">
                    <img src="{{ theme_asset('images/footer-social-instagram.svg') }}" alt="" width="18" height="18" loading="lazy"/>
                </a>
                <a href="#" class="opacity-60 transition hover:opacity-100" aria-label="{{ __('frontend.footer_social_telegram') }}" target="_blank" rel="nofollow noopener noreferrer">
                    <img src="{{ theme_asset('images/footer-social-telegram.svg') }}" alt="" width="18" height="18" loading="lazy"/>
                </a>
            </nav>

            <div class="site-footer__copyright pt-6 text-center text-sm text-gray-500 dark:text-gray-500 md:pt-8">
                <p class="mb-0">&copy; {{ date('Y') }} {{ $siteName }}. {{ __('frontend.all_rights_reserved') }}</p>
            </div>
        </div>
    </footer>

</body>
</html>
