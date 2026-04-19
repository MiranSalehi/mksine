@php
    $pricingFeatureKeys = [
        'home_pricing_feat_records',
        'home_pricing_feat_calendar',
        'home_pricing_feat_visits',
        'home_pricing_feat_inventory',
        'home_pricing_feat_sms',
        'home_pricing_feat_consult',
        'home_pricing_feat_accounting',
        'home_pricing_feat_promo',
        'home_pricing_feat_surveys',
        'home_pricing_feat_staff',
    ];
    $pricingPlans = [
        [
            'featured' => true,
            'top_badge_key' => 'home_pricing_plan_free_float_badge',
            'icon' => 'gift',
            'icon_gradient' => 'from-emerald-500 to-green-600 shadow-emerald-500/25',
            'card_classes' => 'border-emerald-200 ring-2 ring-emerald-200/50 shadow-emerald-500/10 dark:border-emerald-800 dark:ring-emerald-900/40 dark:shadow-emerald-900/20',
            'title_key' => 'home_pricing_plan_free_title',
            'subtitle_key' => 'home_pricing_plan_free_subtitle',
            'show_new_user_panel' => true,
            'new_user_title_key' => 'home_pricing_plan_free_panel_title',
            'new_user_body_key' => 'home_pricing_plan_free_panel_body',
            'price_free' => true,
            'price_key' => 'home_pricing_plan_free_price',
            'period_key' => 'home_pricing_plan_free_period',
            'show_discount' => false,
            'quota_sms_key' => null,
            'quota_storage_key' => null,
            'features' => [1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
        ],
        [
            'featured' => false,
            'top_badge_key' => null,
            'icon' => 'zap',
            'icon_gradient' => 'from-indigo-500 to-indigo-600 shadow-indigo-500/25',
            'card_classes' => 'border-gray-200/50 hover:border-indigo-200/50 dark:border-slate-700 dark:hover:border-indigo-800',
            'title_key' => 'home_pricing_plan_office_title',
            'subtitle_key' => 'home_pricing_plan_office_subtitle',
            'show_new_user_panel' => false,
            'new_user_title_key' => null,
            'new_user_body_key' => null,
            'price_free' => false,
            'price_key' => 'home_pricing_plan_office_price',
            'period_key' => 'home_pricing_period_year',
            'show_discount' => true,
            'quota_sms_key' => 'home_pricing_office_sms_short',
            'quota_storage_key' => 'home_pricing_office_storage_short',
            'features' => [1, 1, 1, 1, 1, 1, 0, 0, 0, 0],
        ],
        [
            'featured' => false,
            'top_badge_key' => null,
            'icon' => 'star',
            'icon_gradient' => 'from-violet-500 to-purple-600 shadow-violet-500/25',
            'card_classes' => 'border-gray-200/50 hover:border-violet-200/50 dark:border-slate-700 dark:hover:border-violet-800',
            'title_key' => 'home_pricing_plan_clinic_title',
            'subtitle_key' => 'home_pricing_plan_clinic_subtitle',
            'show_new_user_panel' => false,
            'new_user_title_key' => null,
            'new_user_body_key' => null,
            'price_free' => false,
            'price_key' => 'home_pricing_plan_clinic_price',
            'period_key' => 'home_pricing_period_year',
            'show_discount' => true,
            'quota_sms_key' => 'home_pricing_clinic_sms_short',
            'quota_storage_key' => 'home_pricing_clinic_storage_short',
            'features' => [1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
        ],
    ];
@endphp

<section
    class="home-pricing-plans relative overflow-hidden bg-gradient-to-br from-slate-50 via-white to-blue-50 py-12 sm:py-16 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950"
    dir="{{ in_array(app()->getLocale(), ['fa', 'ar'], true) ? 'rtl' : 'ltr' }}"
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    aria-labelledby="home-pricing-heading"
    aria-describedby="home-pricing-subheading"
>
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div
            class="absolute -top-40 -right-32 h-80 w-80 rounded-full bg-gradient-to-br from-blue-100/30 to-indigo-100/30 blur-3xl dark:from-blue-900/20 dark:to-indigo-900/20"
        ></div>
        <div
            class="absolute -bottom-40 -left-32 h-80 w-80 rounded-full bg-gradient-to-tr from-emerald-100/30 to-green-100/30 blur-3xl dark:from-emerald-900/15 dark:to-green-900/15"
        ></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <header class="mb-12 text-center lg:mb-16">
            <div
                class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/30 bg-white/60 px-4 py-2 text-sm font-medium text-indigo-700 shadow-lg backdrop-blur-xl lg:mb-8 lg:px-5 lg:py-2.5 dark:border-slate-600/50 dark:bg-slate-800/80 dark:text-indigo-300 dark:shadow-black/25"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="h-4 w-4 shrink-0 text-indigo-600 dark:text-indigo-400"
                    aria-hidden="true"
                >
                    <path
                        d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"
                    ></path>
                    <path d="M20 3v4"></path>
                    <path d="M22 5h-4"></path>
                    <path d="M4 17v2"></path>
                    <path d="M5 18H3"></path>
                </svg>
                {{ __('mksine::frontend.home_pricing_badge') }}
            </div>
            <h2
                id="home-pricing-heading"
                class="mb-6 text-3xl leading-tight font-bold text-gray-900 sm:text-4xl lg:mb-8 lg:text-5xl xl:text-6xl dark:text-gray-50"
            >
                {{ __('mksine::frontend.home_pricing_title_prefix') }}<span
                    class="bg-gradient-to-r from-indigo-600 to-indigo-500 bg-clip-text text-transparent dark:from-indigo-400 dark:to-violet-400"
                >{{ __('mksine::frontend.home_pricing_title_accent') }}</span>
            </h2>
            <p
                id="home-pricing-subheading"
                class="mx-auto max-w-3xl text-pretty text-lg leading-relaxed text-gray-600 lg:text-xl dark:text-gray-400"
            >
                {{ __('mksine::frontend.home_pricing_subtitle') }}
            </p>
        </header>

        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3 lg:gap-6">
            @foreach ($pricingPlans as $plan)
                <div
                    class="{{ ($plan['featured'] ?? false) ? 'lg:-translate-y-2 lg:scale-105' : '' }} min-w-0 group relative transition-all duration-500 hover:scale-[1.02] lg:hover:scale-105"
                >
                    @if (! empty($plan['top_badge_key']))
                        <div class="absolute -top-3 start-1/2 z-20 -translate-x-1/2">
                            <div
                                class="relative rounded-full border-2 border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 shadow-lg backdrop-blur-sm dark:border-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300"
                            >
                                {{ __('mksine::frontend.'.$plan['top_badge_key']) }}
                            </div>
                        </div>
                    @endif
                    <div
                        class="{{ $plan['card_classes'] }} flex min-h-[22rem] flex-col rounded-2xl border bg-white/80 p-3 shadow-xl backdrop-blur-sm transition-all duration-500 group-hover:bg-white/95 group-hover:shadow-2xl sm:min-h-[28rem] sm:rounded-3xl sm:p-5 dark:bg-slate-800/90 dark:group-hover:bg-slate-800 dark:group-hover:shadow-black/45 lg:p-6"
                    >
                        <div class="relative mb-4 shrink-0 text-center sm:mb-6">
                            <div
                                class="{{ $plan['icon_gradient'] }} mx-auto mb-2 flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br shadow-lg transition-transform duration-300 group-hover:scale-110 sm:mb-3 sm:h-12 sm:w-12 sm:rounded-2xl"
                            >
                                @include('mksine::themes.mksine.partials.home.pricing-plan-icon', ['name' => $plan['icon']])
                            </div>
                            <h3 class="mb-1 line-clamp-2 text-xs font-bold text-gray-900 sm:mb-2 sm:text-lg dark:text-gray-50">
                                {{ __('mksine::frontend.'.$plan['title_key']) }}
                            </h3>
                            <p class="mb-2 line-clamp-3 text-[0.65rem] leading-snug text-gray-600 sm:mb-3 sm:text-sm dark:text-gray-400">
                                {{ __('mksine::frontend.'.$plan['subtitle_key']) }}
                            </p>

                            @if (! empty($plan['show_new_user_panel']))
                                <div
                                    class="mb-3 rounded-xl border border-emerald-200/50 bg-gradient-to-r from-emerald-50 to-green-50 p-2.5 backdrop-blur-sm dark:border-emerald-800/50 dark:from-emerald-950/40 dark:to-green-950/30"
                                >
                                    <div
                                        class="mb-1 flex items-center justify-center gap-2 text-emerald-700 dark:text-emerald-300"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="24"
                                            height="24"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="h-3 w-3 shrink-0"
                                            aria-hidden="true"
                                        >
                                            <rect x="3" y="8" width="18" height="4" rx="1"></rect>
                                            <path d="M12 8v13"></path>
                                            <path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"></path>
                                            <path
                                                d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"
                                            ></path>
                                        </svg>
                                        <span class="text-xs font-semibold">
                                            {{ __('mksine::frontend.'.$plan['new_user_title_key']) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-emerald-600 dark:text-emerald-400">
                                        {{ __('mksine::frontend.'.$plan['new_user_body_key']) }}
                                    </p>
                                </div>
                            @endif

                            <div class="mb-3">
                                @if (! empty($plan['price_free']))
                                    <div class="mb-1 flex items-center justify-center gap-1">
                                        <span class="text-lg font-black text-gray-900 sm:text-2xl dark:text-gray-50">
                                            {{ __('mksine::frontend.'.$plan['price_key']) }}
                                        </span>
                                    </div>
                                @else
                                    <div class="flex flex-row flex-wrap items-center justify-center gap-x-1 gap-y-1">
                                        <div class="mb-1 flex items-center justify-center gap-0.5 sm:gap-1">
                                            <span class="text-lg font-black text-gray-900 sm:text-2xl dark:text-gray-50">
                                                {{ __('mksine::frontend.'.$plan['price_key']) }}
                                            </span>
                                            <span class="mt-0.5 text-[0.65rem] text-gray-500 sm:mt-1 sm:text-xs dark:text-gray-400">
                                                {{ __('mksine::frontend.home_pricing_currency_label') }}
                                            </span>
                                        </div>
                                        @if (! empty($plan['show_discount']))
                                            <span
                                                class="inline-flex items-center gap-1 rounded-full border border-red-400/20 bg-gradient-to-r from-rose-500 via-red-500 to-rose-600 px-1.5 py-0.5 text-[0.6rem] font-bold text-white shadow-lg shadow-red-500/30 sm:gap-1.5 sm:px-3 sm:py-1.5 sm:text-xs"
                                            >
                                                {{ __('mksine::frontend.home_pricing_discount_percent') }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                                <div class="text-[0.65rem] font-medium text-gray-500 sm:text-xs dark:text-gray-400">
                                    {{ __('mksine::frontend.'.$plan['period_key']) }}
                                </div>
                            </div>

                            @if (! empty($plan['quota_sms_key']) && ! empty($plan['quota_storage_key']))
                                <div
                                    class="mb-3 rounded-xl bg-indigo-50 p-3 backdrop-blur-sm dark:bg-indigo-950/35"
                                >
                                    <div class="flex items-center justify-center gap-4 text-xs">
                                        <div
                                            class="flex items-center gap-1 font-medium text-indigo-700 dark:text-indigo-300"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="24"
                                                height="24"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                class="h-3 w-3 shrink-0"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"
                                                ></path>
                                            </svg>
                                            <span>{{ __('mksine::frontend.'.$plan['quota_sms_key']) }}</span>
                                        </div>
                                        <div
                                            class="flex items-center gap-1 font-medium text-indigo-700 dark:text-indigo-300"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="24"
                                                height="24"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                class="h-3 w-3 shrink-0"
                                                aria-hidden="true"
                                            >
                                                <line x1="22" x2="2" y1="12" y2="12"></line>
                                                <path
                                                    d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"
                                                ></path>
                                                <line x1="6" x2="6.01" y1="16" y2="16"></line>
                                                <line x1="10" x2="10.01" y1="16" y2="16"></line>
                                            </svg>
                                            <span>{{ __('mksine::frontend.'.$plan['quota_storage_key']) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="flex flex-1 flex-col">
                            <div class="grid grid-cols-2 gap-x-1 gap-y-1 sm:gap-x-3 sm:gap-y-2">
                                @foreach ($pricingFeatureKeys as $fi => $featKey)
                                    @include('mksine::themes.mksine.partials.home.pricing-feature-row', [
                                        'included' => (bool) ($plan['features'][$fi] ?? false),
                                        'label' => __('mksine::frontend.'.$featKey),
                                    ])
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
